@php
    $logoPath = public_path('logo.jpeg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $nombreTrabajador = trim($trabajador->nombre ?? trim(($trabajador->nombres ?? '').' '.($trabajador->apellidos ?? ''))) ?: '-';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Kardex EPP - {{ $nombreTrabajador }}</title>
    <style>
        @page { margin: 24px 28px; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        .header td { border: 1px solid #111827; height: 54px; vertical-align: middle; }
        .logo-cell { width: 24%; text-align: center; }
        .logo { max-width: 120px; max-height: 42px; object-fit: contain; }
        .title-cell { width: 48%; text-align: center; color: #0b1f63; font-weight: 700; }
        .format { font-size: 8px; letter-spacing: 6px; margin-bottom: 6px; }
        .title { font-size: 14px; letter-spacing: 4px; }
        .code-cell { width: 28%; text-align: center; line-height: 1.6; }
        .bar { margin-top: 8px; background: #485563; color: #fff; font-weight: 700; letter-spacing: 5px; text-align: center; padding: 4px 0; }
        .info td { border: 1px solid #9ca3af; height: 24px; padding: 4px 6px; }
        .info .label { width: 16%; background: #d9e9fb; color: #0b1f63; font-weight: 700; }
        .info .value { width: 34%; font-weight: 600; }
        .summary td { border: 1px solid #9ca3af; padding: 5px 7px; text-align: center; }
        .summary .label { background: #eef2f7; color: #334155; font-weight: 700; }
        .items { margin-top: 8px; }
        .items th { border: 1px solid #111827; background: #1f2f78; color: #fff; font-size: 7px; padding: 4px 3px; text-align: center; }
        .items td { border: 1px solid #9ca3af; height: 22px; padding: 3px 4px; vertical-align: middle; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .signatures { margin-top: 10px; }
        .signatures td { border: 1px solid #111827; height: 46px; width: 33.333%; vertical-align: bottom; padding: 5px 7px; }
        .sign-title { margin-bottom: 18px; color: #0b1f63; font-weight: 700; }
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
                <div class="format">FORMATO</div>
                <div class="title">KARDEX DE ENTREGA EPP</div>
            </td>
            <td class="code-cell">
                <div><strong>Codigo:</strong> FR-EPP-01</div>
                <div><strong>Version:</strong> 00</div>
                <div><strong>Emision:</strong> 01/09/2026</div>
            </td>
        </tr>
    </table>

    <div class="bar">DATOS DEL TRABAJADOR</div>
    <table class="info">
        <tr>
            <td class="label">Trabajador</td>
            <td class="value">{{ $nombreTrabajador }}</td>
            <td class="label">DNI</td>
            <td class="value">{{ $trabajador->dni ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Cargo</td>
            <td class="value">{{ $trabajador->cargo ?? '-' }}</td>
            <td class="label">Area</td>
            <td class="value">{{ $trabajador->area ?? '-' }}</td>
        </tr>
    </table>

    <table class="summary" style="margin-top: 8px;">
        <tr>
            <td class="label">Total asignaciones</td>
            <td>{{ $asignaciones->count() }}</td>
            <td class="label">Vigentes</td>
            <td>{{ $asignaciones->where('estado', 'entregado')->count() }}</td>
            <td class="label">Devueltas</td>
            <td>{{ $asignaciones->where('estado', 'devuelto')->count() }}</td>
            <td class="label">Renovadas / bajas</td>
            <td>{{ $asignaciones->whereIn('estado', ['renovado', 'baja'])->count() }}</td>
        </tr>
    </table>

    <div class="bar">HISTORIAL DE ENTREGA</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">IT</th>
                <th style="width: 12%;">FECHA</th>
                <th style="width: 14%;">EVENTO</th>
                <th style="width: 18%;">CODIGO</th>
                <th style="width: 38%;">EPP / PRODUCTO</th>
                <th style="width: 12%;">CANT.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($eventos as $evento)
                @php($asignacion = $evento['asignacion'])
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ optional($evento['fecha'])->format('d/m/Y') }}</td>
                    <td class="center">{{ strtoupper($evento['tipo']) }}</td>
                    <td class="center">{{ $asignacion->codigo }}</td>
                    <td>{{ trim(($asignacion->producto?->codigo ? $asignacion->producto->codigo.' - ' : '').($asignacion->producto?->nombre ?? '-')) }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $asignacion->cantidad, 2), '0'), '.') }}</td>
                </tr>
                @if ($evento['observaciones'])
                    <tr>
                        <td></td>
                        <td colspan="5" class="muted"><strong>Observacion:</strong> {{ $evento['observaciones'] }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" class="center">El trabajador no tiene EPPs registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sign-title">Trabajador:</div>V°B° {{ strtoupper($nombreTrabajador) }}</td>
            <td><div class="sign-title">Responsable:</div>V°B°</td>
            <td><div class="sign-title">Recepcion conforme:</div>V°B°</td>
        </tr>
    </table>
</body>
</html>
