@php
    $logoPath = public_path('logo.jpeg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;

    $trabajador = $prestamo->trabajador;
    $trabajadorNombre = trim($trabajador->nombre ?? trim(($trabajador->nombres ?? '').' '.($trabajador->apellidos ?? ''))) ?: '-';
    $detalles = $prestamo->detalles ?? collect();
    $rows = 10;
    $total = $detalles->sum(fn ($detalle) => (float) $detalle->cantidad);
    $devueltos = $prestamo->estado === 'devuelto' ? $total : 0;
    $enPrestamo = $total - $devueltos;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $prestamo->codigo }}</title>
    <style>
        @page { margin: 8mm 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #00133a; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        .header { table-layout: fixed; }
        .header td { border: 1.4px solid #333; height: 15mm; vertical-align: middle; text-align: center; }
        .logo { width: 18% !important; padding: 1mm; }
        .logo img { max-width: 18mm; max-height: 10mm; object-fit: contain; }
        .title { width: 58% !important; color: #14276d; font-weight: bold; letter-spacing: 5px; }
        .title .small { margin-bottom: 1.5mm; color: #333; font-weight: normal; letter-spacing: 5px; }
        .title .big { font-size: 12px; line-height: 1.25; }
        .code { width: 24% !important; color: #000; font-size: 8px; line-height: 1.45; text-align: left; padding-left: 2mm; }
        .section-title { margin-top: 3mm; background: #707784; color: #fff; text-align: center; font-weight: bold; letter-spacing: 4px; padding: 1.8mm; }
        .worker td { border: 1px solid #aaa; height: 7mm; color: #000; }
        .worker .label { width: 38mm; background: #d9e9fb; color: #09236b; font-weight: bold; padding-left: 3mm; }
        .worker .value { padding-left: 3mm; }
        .items { margin-top: 3mm; table-layout: fixed; }
        .items th { background: #233278; color: #fff; border: 1px solid #2e438a; height: 7mm; font-size: 8px; letter-spacing: 1px; }
        .items td { border: 1px solid #c7c7c7; height: 7mm; color: #000; vertical-align: middle; }
        .center { text-align: center; }
        .desc { padding-left: 2mm; }
        .summary { margin-top: 2mm; }
        .pill { display: inline-block; margin-right: 3mm; border-radius: 3px; padding: 1.6mm 3mm; font-weight: bold; }
        .blue { background: #dbeafe; color: #0f3b8f; }
        .green { background: #d1fae5; color: #047857; }
        .red { background: #fee2e2; color: #b91c1c; }
        .note { margin-top: 4mm; border: 1px solid #e8dfb8; background: #fffbe8; padding: 3mm; color: #14213d; line-height: 1.45; font-size: 8px; }
        .place { margin-top: 4mm; text-align: right; font-style: italic; color: #334155; }
        .signatures { margin-top: 4mm; }
        .signatures td { width: 50%; height: 24mm; border: 1px solid #aaa; vertical-align: bottom; padding: 3mm; color: #001b66; font-weight: bold; }
        .line { border-top: 1px solid #555; margin-bottom: 2mm; }
        .muted { font-weight: normal; color: #64748b; }
    </style>
</head>
<body>
    <table class="header">
        <colgroup>
            <col style="width: 18%;">
            <col style="width: 58%;">
            <col style="width: 24%;">
        </colgroup>
        <tr>
            <td class="logo">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo">
                @else
                    <strong>LOGISTICA</strong>
                @endif
            </td>
            <td class="title">
                <div class="small">FORMATO</div>
                <div class="big">CONTROL DE ENTREGA DE HERRAMIENTAS</div>
            </td>
            <td class="code">
                <div><strong>Codigo:</strong> FR-ALM-03</div>
                <div><strong>Version:</strong> 00</div>
                <div><strong>Fecha:</strong> 01/09/2026</div>
            </td>
        </tr>
    </table>

    <div class="section-title">DATOS DEL TRABAJADOR</div>
    <table class="worker">
        <tr>
            <td class="label">Apellidos y Nombres</td>
            <td class="value" colspan="3"><strong>{{ $trabajadorNombre }}</strong></td>
        </tr>
        <tr>
            <td class="label">DNI</td>
            <td class="value">{{ $trabajador->dni ?? '-' }}</td>
            <td class="label">Puesto de Trabajo</td>
            <td class="value">{{ $trabajador->cargo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Obra / Unidad</td>
            <td class="value" colspan="3">{{ $prestamo->centroCosto->nombre ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">REGISTRO DE HERRAMIENTAS / EQUIPOS</div>
    <table class="items">
        <thead>
            <tr>
                <th rowspan="2" style="width: 8mm;">IT</th>
                <th rowspan="2" style="width: 12mm;">CANT.</th>
                <th rowspan="2">DESCRIPCION</th>
                <th colspan="2" style="width: 42mm;">ENTREGA</th>
                <th colspan="3" style="width: 54mm;">DEVOLUCION</th>
            </tr>
            <tr>
                <th>FECHA</th>
                <th>FIRMA</th>
                <th>CANT.</th>
                <th>FECHA</th>
                <th>FIRMA</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $rows; $i++)
                @php($detalle = $detalles[$i] ?? null)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="center">{{ $detalle ? rtrim(rtrim(number_format((float) $detalle->cantidad, 2), '0'), '.') : '' }}</td>
                    <td class="desc">
                        @if ($detalle)
                            {{ strtoupper($detalle->producto->nombre ?? '') }}
                            @if ($detalle->producto->codigo ?? null)
                                [{{ $detalle->producto->codigo }}]
                            @endif
                        @endif
                    </td>
                    <td class="center">{{ $detalle ? optional($prestamo->fecha_prestamo)->format('d/m/Y') : '' }}</td>
                    <td></td>
                    <td class="center">{{ $detalle && $prestamo->estado === 'devuelto' ? rtrim(rtrim(number_format((float) $detalle->cantidad_devuelta, 2), '0'), '.') : '' }}</td>
                    <td class="center">{{ $detalle && $prestamo->fecha_devolucion ? optional($prestamo->fecha_devolucion)->format('d/m/Y') : '' }}</td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="summary">
        <span class="pill blue">Total: {{ rtrim(rtrim(number_format($total, 2), '0'), '.') }}</span>
        <span class="pill green">En prestamo: {{ rtrim(rtrim(number_format($enPrestamo, 2), '0'), '.') }}</span>
        <span class="pill red">Devueltos/Otros: {{ rtrim(rtrim(number_format($devueltos, 2), '0'), '.') }}</span>
    </div>

    <div class="note">
        <strong>NOTA:</strong><br>
        El trabajador es responsable de las herramientas y/o equipos recibidos, debiendo devolverlos en las mismas condiciones en que fueron entregados. La perdida o dano injustificado de los mismos sera de responsabilidad del trabajador, quien debera reponer el bien o asumir el costo correspondiente conforme a la politica interna de la empresa.
    </div>

    <div class="place">Huancayo, {{ now()->translatedFormat('d \d\e F \d\e Y') }}</div>

    <table class="signatures">
        <tr>
            <td>
                Firma del Trabajador:
                <div style="height: 18mm;"></div>
                <div class="line"></div>
                <div style="text-align: center;">{{ strtoupper($trabajadorNombre) }}</div>
                <div class="muted" style="text-align: center;">DNI: {{ $trabajador->dni ?? '-' }}</div>
            </td>
            <td>
                Responsable del Registro:
                <div style="height: 18mm;"></div>
                <div class="line"></div>
                <div style="text-align: center;">{{ strtoupper($prestamo->usuario->name ?? 'ALMACEN') }}</div>
                <div class="muted" style="text-align: center;">Sello y Firma:</div>
            </td>
        </tr>
    </table>
</body>
</html>
