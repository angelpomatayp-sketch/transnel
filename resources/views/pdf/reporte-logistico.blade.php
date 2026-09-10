@php
    $logoPath = public_path('logo.jpeg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;

    $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $format = function ($value, int $index) use ($report, $money, $number) {
        $column = strtolower($report['columns'][$index] ?? '');

        if (str_contains($column, 'valor') || str_contains($column, 'costo')) {
            return $money($value);
        }

        return is_numeric($value) ? $number($value) : ($value ?: '-');
    };
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page { margin: 24px 28px; size: A4 landscape; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: DejaVu Sans, Arial, sans-serif; font-size: 7.5px; }
        table { width: 100%; border-collapse: collapse; }
        .header td { border: 1px solid #111827; height: 48px; vertical-align: middle; }
        .logo-cell { width: 22%; text-align: center; }
        .logo { max-width: 110px; max-height: 38px; object-fit: contain; }
        .title-cell { width: 50%; text-align: center; color: #0b1f63; font-weight: 700; }
        .format { font-size: 7px; letter-spacing: 5px; margin-bottom: 5px; }
        .title { font-size: 13px; letter-spacing: 3px; }
        .code-cell { width: 28%; text-align: center; line-height: 1.6; }
        .bar { margin-top: 7px; background: #485563; color: #fff; font-weight: 700; letter-spacing: 4px; text-align: center; padding: 4px 0; }
        .meta td { border: 1px solid #9ca3af; padding: 4px 6px; }
        .meta .label { background: #d9e9fb; color: #0b1f63; font-weight: 700; width: 14%; }
        .summary { margin-top: 7px; }
        .summary td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: center; }
        .summary .label { background: #eef2f7; color: #334155; font-weight: 700; }
        .items { margin-top: 8px; table-layout: fixed; }
        .items th { border: 1px solid #111827; background: #1f2f78; color: #fff; font-size: 6.5px; padding: 4px 3px; text-align: center; }
        .items td { border: 1px solid #9ca3af; height: 20px; padding: 3px 4px; vertical-align: middle; word-wrap: break-word; }
        .center { text-align: center; }
        .right { text-align: right; }
        .footer { margin-top: 8px; color: #64748b; font-size: 6.5px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if ($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                @else
                    <strong>LOGISTICA</strong>
                @endif
            </td>
            <td class="title-cell">
                <div class="format">REPORTE</div>
                <div class="title">{{ strtoupper($report['title']) }}</div>
            </td>
            <td class="code-cell">
                <div><strong>Vista:</strong> {{ $scope['titulo'] ?? '-' }}</div>
                <div><strong>Desde:</strong> {{ $filters['desde'] ?: '-' }}</div>
                <div><strong>Hasta:</strong> {{ $filters['hasta'] ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="bar">FILTROS</div>
    <table class="meta">
        <tr>
            <td class="label">Producto</td>
            <td>{{ $filterLabels['producto'] ?? 'Todos' }}</td>
            <td class="label">Almacen</td>
            <td>{{ $filterLabels['almacen'] ?? 'Todos' }}</td>
            <td class="label">Centro</td>
            <td>{{ $filterLabels['centro'] ?? 'Todos' }}</td>
        </tr>
    </table>

    @if (! empty($report['summary']))
        <table class="summary">
            <tr>
                @foreach ($report['summary'] as $item)
                    <td class="label">{{ $item['label'] }}</td>
                    <td>{{ $item['type'] === 'money' ? $money($item['value']) : $number($item['value']) }}</td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="items">
        <thead>
            <tr>
                @foreach ($report['columns'] as $column)
                    <th>{{ strtoupper($column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td>{{ $format($value, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="{{ count($report['columns']) }}">No hay datos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}. Total registros: {{ count($report['rows']) }}.
    </div>
</body>
</html>
