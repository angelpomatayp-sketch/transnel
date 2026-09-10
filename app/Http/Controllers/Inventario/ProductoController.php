<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Familia;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['buscar', 'familia', 'per_page']);
        $perPage = in_array((int) ($filters['per_page'] ?? 10), [10, 20, 50], true)
            ? (int) ($filters['per_page'] ?? 10)
            : 10;

        $familias = Familia::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'es_epp', 'categoria_epp']);

        return Inertia::render('Inventario/Productos/Index', [
            'productos' => Producto::query()
                ->with(['familia:id,codigo,nombre,es_epp,categoria_epp', 'unidad:id,codigo,nombre,abreviatura'])
                ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                    $query->where(function ($subquery) use ($buscar) {
                        $subquery
                            ->where('codigo', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%")
                            ->orWhere('marca', 'like', "%{$buscar}%")
                            ->orWhere('modelo', 'like', "%{$buscar}%");
                    });
                })
                ->when($filters['familia'] ?? null, fn ($query, $familiaId) => $query->where('familia_id', $familiaId))
                ->orderBy('nombre')
                ->paginate($perPage)
                ->withQueryString(),
            'familias' => $familias,
            'unidades' => UnidadMedida::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'filters' => [
                'buscar' => $filters['buscar'] ?? '',
                'familia' => $filters['familia'] ?? '',
                'per_page' => $perPage,
            ],
            'codigosSugeridos' => $familias
                ->mapWithKeys(fn (Familia $familia) => [$familia->id => $this->nextCodePreview($familia)])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $familia = $data['familia_id'] ? Familia::query()->find($data['familia_id']) : null;

        $data['codigo'] = $this->nextCode($familia);
        $data = $this->applyEppRulesFromFamily($data, $familia);
        $data['imagenes'] = $this->storeImages($request);

        Producto::query()->create($data);

        return back()->with('success', 'Producto registrado.');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $this->validatedData($request, $producto);
        $familia = $data['familia_id'] ? Familia::query()->find($data['familia_id']) : null;

        if ((int) $producto->familia_id !== (int) ($data['familia_id'] ?? 0)) {
            $data['codigo'] = $this->nextCode($familia, $producto);
        }

        $data = $this->applyEppRulesFromFamily($data, $familia);

        if ($request->hasFile('imagenes')) {
            $data['imagenes'] = $this->storeImages($request, $producto);
        }

        $producto->update($data);

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return back()->with('success', 'Producto eliminado.');
    }

    public function export(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['codigo', 'nombre', 'familia', 'unidad', 'marca', 'modelo', 'stock_min', 'activo', 'es_epp', 'categoria_epp'],
        ]);

        Producto::query()
            ->with(['familia:id,codigo,nombre,es_epp,categoria_epp', 'unidad:id,codigo,nombre'])
            ->orderBy('nombre')
            ->get()
            ->each(function (Producto $producto, int $index) use ($sheet) {
                $sheet->fromArray([[
                    $producto->codigo,
                    $producto->nombre,
                    $producto->familia?->nombre,
                    $producto->unidad?->nombre,
                    $producto->marca,
                    $producto->modelo,
                    (int) $producto->stock_minimo,
                    $producto->activo ? 'SI' : 'NO',
                    $producto->familia?->es_epp ? 'SI' : 'NO',
                    $producto->familia?->categoria_epp,
                ]], null, 'A'.($index + 2));
            });

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $spreadsheet = IOFactory::load($request->file('archivo')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $familias = Familia::query()
            ->where('activo', true)
            ->get(['id', 'codigo', 'nombre', 'es_epp', 'categoria_epp']);
        $unidades = UnidadMedida::query()
            ->where('activo', true)
            ->get(['id', 'codigo', 'nombre', 'abreviatura']);
        $prepared = [];
        $errors = [];
        $generatedCodes = [];

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            $nombre = trim((string) ($row['B'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $categoriaEpp = $this->normalizeEppCategory($row['J'] ?? null);
            $esEpp = $this->excelBoolean($row['I'] ?? null, false) || $categoriaEpp !== null;
            $familia = $this->findFamiliaForImport($familias, $row['C'] ?? null, $categoriaEpp, $esEpp);
            $unidad = $this->findUnidadForImport($unidades, $row['D'] ?? null);

            if (! $familia) {
                $errors[] = "Fila {$rowNumber}: familia no encontrada.";
                continue;
            }

            if (! $unidad) {
                $errors[] = "Fila {$rowNumber}: unidad no encontrada.";
                continue;
            }

            $codigo = trim((string) ($row['A'] ?? '')) ?: $this->nextImportCode($familia, $generatedCodes);

            $data = $this->applyEppRulesFromFamily([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'familia_id' => $familia->id,
                'unidad_medida_id' => $unidad->id,
                'descripcion' => null,
                'marca' => trim((string) ($row['E'] ?? '')) ?: null,
                'modelo' => trim((string) ($row['F'] ?? '')) ?: null,
                'stock_minimo' => max(0, (int) ($row['G'] ?? 0)),
                'stock_maximo' => null,
                'ubicacion' => null,
                'lote' => null,
                'activo' => $this->excelBoolean($row['H'] ?? null, true),
                'vida_util_dias' => null,
                'dias_alerta_vencimiento' => null,
                'requiere_talla' => false,
                'tallas_disponibles' => null,
                'imagenes' => [],
            ], $familia);

            $prepared[] = $data;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'archivo' => implode(' ', array_slice($errors, 0, 5)),
            ]);
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $data) {
                $imagenes = $data['imagenes'] ?? [];
                $existing = Producto::query()->where('codigo', $data['codigo'])->first();

                if ($existing && $existing->imagenes) {
                    $imagenes = $existing->imagenes;
                }

                $data['imagenes'] = $imagenes;

            Producto::query()->updateOrCreate(
                ['codigo' => $data['codigo']],
                $data,
            );
            }
        });

        if ($prepared === []) {
            return back()->with('success', 'No se encontraron productos para importar.');
        }

        return back()->with('success', 'Productos importados.');
    }

    private function validatedData(Request $request, ?Producto $producto = null): array
    {
        $data = $request->validate([
            'familia_id' => ['nullable', 'exists:familias,id'],
            'unidad_medida_id' => ['required', Rule::exists((new UnidadMedida())->getTable(), 'id')],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'lote' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'vida_util_dias' => ['nullable', 'integer', 'min:1'],
            'dias_alerta_vencimiento' => ['nullable', 'integer', 'min:1'],
            'dias_alerta' => ['nullable', 'integer', 'min:1'],
            'requiere_talla' => ['nullable', 'boolean'],
            'tallas_texto' => ['nullable', 'string', 'max:255'],
            'tallas' => ['nullable', 'string', 'max:255'],
            'imagenes' => ['nullable', 'array', 'max:4'],
            'imagenes.*' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['stock_minimo'] = (int) ($data['stock_minimo'] ?? 0);
        $data['requiere_talla'] = (bool) ($data['requiere_talla'] ?? false);

        return $data;
    }

    private function applyEppRulesFromFamily(array $data, ?Familia $familia): array
    {
        $isEpp = (bool) ($familia?->es_epp);
        $data['es_epp'] = $isEpp;

        if (! $isEpp) {
            $data['vida_util_dias'] = null;
            $data['dias_alerta_vencimiento'] = null;
            $data['dias_alerta'] = null;
            $data['requiere_talla'] = false;
            $data['tallas_disponibles'] = null;
            $data['tallas'] = null;
        } else {
            $data['vida_util_dias'] = $data['vida_util_dias'] ?: 365;
            $data['dias_alerta_vencimiento'] = ($data['dias_alerta_vencimiento'] ?? $data['dias_alerta'] ?? null) ?: 30;
            $data['dias_alerta'] = $data['dias_alerta_vencimiento'];
            $data['requiere_talla'] = (bool) ($data['requiere_talla'] ?? false);
            $tallasTexto = $data['tallas_texto'] ?? $data['tallas'] ?? null;
            $data['tallas_disponibles'] = $data['requiere_talla']
                ? collect(explode(',', (string) $tallasTexto))
                    ->map(fn (string $talla) => trim($talla))
                    ->filter()
                    ->values()
                    ->all()
                : null;
            $data['tallas'] = $data['tallas_disponibles'] ? implode(', ', $data['tallas_disponibles']) : null;
        }

        unset($data['tallas_texto']);

        return $data;
    }

    private function storeImages(Request $request, ?Producto $producto = null): array
    {
        if ($producto?->imagenes) {
            foreach ($producto->imagenes as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        return collect($request->file('imagenes', []))
            ->take(4)
            ->map(fn ($image) => $image->store('productos', 'public'))
            ->values()
            ->all();
    }

    private function nextCodePreview(?Familia $familia): string
    {
        return $this->nextCode($familia);
    }

    private function nextCode(?Familia $familia, ?Producto $ignore = null): string
    {
        $prefix = $familia?->codigo ?: 'PRD';
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
        $max = Producto::withTrashed()
            ->where('codigo', 'like', "{$prefix}-%")
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->pluck('codigo')
            ->reduce(function (int $currentMax, string $code) use ($pattern) {
                if (! preg_match($pattern, $code, $matches)) {
                    return $currentMax;
                }

                return max($currentMax, (int) $matches[1]);
            }, 0);

        return sprintf('%s-%03d', $prefix, $max + 1);
    }

    private function nextImportCode(Familia $familia, array &$generatedCodes): string
    {
        $prefix = $familia->codigo ?: 'PRD';

        if (! isset($generatedCodes[$prefix])) {
            $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
            $generatedCodes[$prefix] = Producto::withTrashed()
                ->where('codigo', 'like', "{$prefix}-%")
                ->pluck('codigo')
                ->reduce(function (int $currentMax, string $code) use ($pattern) {
                    if (! preg_match($pattern, $code, $matches)) {
                        return $currentMax;
                    }

                    return max($currentMax, (int) $matches[1]);
                }, 0) + 1;
        }

        return sprintf('%s-%03d', $prefix, $generatedCodes[$prefix]++);
    }

    private function findFamiliaForImport($familias, mixed $value, ?string $categoriaEpp, bool $esEpp): ?Familia
    {
        $needle = $this->normalizeImportText($value);

        if ($needle !== '') {
            $familia = $familias->first(function (Familia $familia) use ($needle) {
                return $this->normalizeImportText($familia->codigo) === $needle
                    || $this->normalizeImportText($familia->nombre) === $needle;
            });

            if ($familia) {
                return $familia;
            }
        }

        if ($categoriaEpp) {
            $categoria = $this->normalizeImportText($categoriaEpp);
            $familia = $familias->first(function (Familia $familia) use ($categoria) {
                return $familia->es_epp
                    && $this->normalizeImportText($familia->categoria_epp) === $categoria;
            });

            if ($familia) {
                return $familia;
            }
        }

        if ($esEpp && $needle !== '') {
            return $familias->first(function (Familia $familia) use ($needle) {
                return $familia->es_epp
                    && Str::contains($this->normalizeImportText($familia->nombre), $needle);
            });
        }

        return null;
    }

    private function findUnidadForImport($unidades, mixed $value): ?UnidadMedida
    {
        $needle = $this->normalizeImportText($value);

        if ($needle === '') {
            return null;
        }

        return $unidades->first(function (UnidadMedida $unidad) use ($needle) {
            return $this->normalizeImportText($unidad->codigo) === $needle
                || $this->normalizeImportText($unidad->nombre) === $needle
                || $this->normalizeImportText($unidad->abreviatura) === $needle;
        });
    }

    private function normalizeEppCategory(mixed $value): ?string
    {
        $normalized = $this->normalizeImportText($value);

        if ($normalized === '') {
            return null;
        }

        $categories = [
            'Protección de Cabeza' => ['cabeza', 'casco', 'proteccion de cabeza'],
            'Protección Ocular' => ['ocular', 'ojos', 'lentes', 'proteccion ocular'],
            'Protección Auditiva' => ['auditiva', 'oidos', 'orejas', 'proteccion auditiva'],
            'Protección Respiratoria' => ['respiratoria', 'respirador', 'mascarilla', 'proteccion respiratoria'],
            'Protección de Manos' => ['manos', 'guantes', 'proteccion de manos'],
            'Protección de Pies' => ['pies', 'zapatos', 'botas', 'proteccion de pies'],
            'Protección Corporal' => ['corporal', 'cuerpo', 'ropa', 'proteccion corporal'],
            'Trabajo en Altura' => ['altura', 'arnes', 'trabajo en altura'],
            'Otros' => ['otros', 'otro'],
        ];

        foreach ($categories as $category => $aliases) {
            foreach ($aliases as $alias) {
                if ($normalized === $alias || Str::contains($normalized, $alias)) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function excelBoolean(mixed $value, bool $default): bool
    {
        $normalized = $this->normalizeImportText($value);

        if ($normalized === '') {
            return $default;
        }

        return in_array($normalized, ['1', 'si', 's', 'yes', 'y', 'true', 'activo', 'activa'], true);
    }

    private function normalizeImportText(mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '';
        }

        $text = mb_strtolower(Str::ascii($text));

        return preg_replace('/\s+/', ' ', $text) ?: '';
    }
}
