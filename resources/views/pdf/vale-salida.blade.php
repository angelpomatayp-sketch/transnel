@php
    $logoPath = public_path('logo.jpeg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;

    $detalles = $vale->detalles ?? collect();
    $rows = max(10, $detalles->count());
    $estado = strtoupper($vale->estado ?? 'PENDIENTE');
    $estadoClass = $estado === 'ENTREGADO' ? 'status-ok' : 'status-pending';
    $trabajadorNombre = trim(collect([
        $vale->trabajador->nombres ?? null,
        $vale->trabajador->apellidos ?? null,
    ])->filter()->join(' ')) ?: ($vale->trabajador->nombre ?? null);
    $receptor = $vale->receptor_nombre
        ?: ($vale->entregado_a
        ?: ($trabajadorNombre
        ?: ($vale->destino ?: '-')));
    $solicitante = $vale->solicitante->name ?? $vale->solicitante->nombre ?? '-';
    $responsable = $solicitante;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $vale->numero }}</title>
    <style>
        @page {
            margin: 10mm 12mm;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7.5px;
        }

        .sheet {
            width: 100%;
            min-height: 100%;
            position: relative;
        }

        .format {
            width: 128mm;
            margin-left: auto;
            margin-right: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border: 1.2px solid #1f1f1f;
            height: 15mm;
            vertical-align: middle;
            text-align: center;
        }

        .logo-cell {
            width: 27mm;
            padding: 2mm;
        }

        .logo-cell img {
            max-width: 18mm;
            max-height: 9mm;
            object-fit: contain;
        }

        .header-title {
            width: 62mm;
            font-weight: bold;
            letter-spacing: 4px;
        }

        .header-title .line {
            border-top: 1px solid #555;
            margin: 0 5mm 2mm;
        }

        .header-title .format-word {
            font-size: 6px;
            letter-spacing: 2px;
            margin-bottom: 2mm;
        }

        .header-title .title {
            font-size: 11px;
        }

        .header-code {
            width: 39mm;
            font-size: 7px;
            font-weight: bold;
            line-height: 1.55;
        }

        .number-box {
            width: 35mm;
            margin: 1.5mm 0 4mm auto;
            border: 1.2px solid #1f1f1f;
            font-weight: bold;
        }

        .number-box td {
            height: 5.5mm;
            text-align: center;
            vertical-align: middle;
        }

        .number-box .label {
            width: 12mm;
            font-size: 9px;
            color: #001d63;
        }

        .meta {
            margin-bottom: 1.5mm;
        }

        .meta td {
            border-bottom: 1px solid #1f1f1f;
            height: 5.5mm;
            vertical-align: middle;
        }

        .meta .label {
            width: 22mm;
            border-bottom: none;
            font-weight: bold;
        }

        .meta .value {
            width: 39mm;
            padding-left: 2mm;
        }

        .meta .label-right {
            width: 27mm;
            border-bottom: none;
            padding-left: 4mm;
            font-weight: bold;
        }

        .meta .value-right {
            width: 40mm;
            padding-left: 2mm;
        }

        .status-ok,
        .status-pending {
            display: inline-block;
            border-radius: 3px;
            padding: 1px 7px;
            font-size: 7px;
            font-weight: bold;
        }

        .status-ok {
            background: #b9f3cd;
            color: #007b37;
        }

        .status-pending {
            background: #fff1b8;
            color: #9a6a00;
        }

        .items {
            border: 1.2px solid #1f1f1f;
        }

        .items th {
            height: 5.5mm;
            border: 1px solid #3d7d42;
            background: #5fb763;
            color: #000;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
        }

        .items td {
            height: 5.4mm;
            border: 1px solid #c5c5c5;
            vertical-align: middle;
        }

        .items .n {
            width: 7mm;
            text-align: center;
        }

        .items .desc {
            width: 62mm;
            padding-left: 2mm;
        }

        .items .unit {
            width: 12mm;
            text-align: center;
        }

        .items .qty {
            width: 13mm;
            text-align: center;
        }

        .items .vb {
            width: 8mm;
            text-align: center;
        }

        .items .obs {
            width: 22mm;
            padding-left: 1.5mm;
        }

        .sign-labels {
            margin-top: 2mm;
            font-size: 7px;
            font-weight: bold;
        }

        .sign-labels td {
            width: 33.33%;
            padding-bottom: 1mm;
        }

        .signatures td {
            width: 33.33%;
            height: 14mm;
            border: 1px solid #1f1f1f;
            vertical-align: bottom;
            padding: 1mm;
            font-size: 6px;
            color: #001d63;
        }

        .note {
            margin-top: 2mm;
            border: 1px solid #bfbfbf;
            border-radius: 2px;
            padding: 2mm;
            color: #4a5568;
            font-size: 6.5px;
            line-height: 1.35;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="format">
            <table class="header">
                <tr>
                    <td class="logo-cell">
                        @if ($logoSrc)
                            <img src="{{ $logoSrc }}" alt="Logo">
                        @else
                            <strong>LOGISTICA</strong>
                        @endif
                    </td>
                    <td class="header-title">
                        <div class="format-word">FORMATO</div>
                        <div class="line"></div>
                        <div class="title">VALE DE SALIDA</div>
                    </td>
                    <td class="header-code">
                        <div>Codigo: FR-ALM-04</div>
                        <div>Version: 00</div>
                        <div>Fecha: 01/09/2026</div>
                    </td>
                </tr>
            </table>

            <table class="number-box">
                <tr>
                    <td class="label">N°</td>
                    <td>{{ $vale->numero }}</td>
                </tr>
            </table>

            <table class="meta">
                <tr>
                    <td class="label">Obra / Unidad</td>
                    <td class="value">{{ $vale->centroCosto->nombre ?? '-' }}</td>
                    <td class="label-right">Fecha de emision</td>
                    <td class="value-right">{{ optional($vale->fecha)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Solicitante</td>
                    <td class="value">{{ $receptor }}</td>
                    <td class="label-right">Responsable</td>
                    <td class="value-right">{{ $responsable }}</td>
                </tr>
                <tr>
                    <td class="label">Almacen</td>
                    <td class="value">{{ $vale->almacen->nombre ?? '-' }}</td>
                    <td class="label-right">Estado</td>
                    <td class="value-right"><span class="{{ $estadoClass }}">{{ $estado }}</span></td>
                </tr>
                <tr>
                    <td class="label">Motivo</td>
                    <td class="value" colspan="3">{{ $vale->motivo ?? '-' }}</td>
                </tr>
            </table>

            <table class="items">
                <thead>
                    <tr>
                        <th class="n">N°</th>
                        <th class="desc">DESCRIPCION</th>
                        <th class="unit">UNIDAD</th>
                        <th class="qty">CANTIDAD</th>
                        <th class="vb">V°B°</th>
                        <th class="obs">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $rows; $i++)
                        @php($detalle = $detalles[$i] ?? null)
                        <tr>
                            <td class="n">{{ $i + 1 }}</td>
                            <td class="desc">{{ $detalle?->producto?->nombre ?? '' }}</td>
                            <td class="unit">{{ strtoupper($detalle?->producto?->unidad?->abreviatura ?? $detalle?->producto?->unidad?->nombre ?? '') }}</td>
                            <td class="qty">
                                @if ($detalle)
                                    {{ rtrim(rtrim(number_format((float) $detalle->cantidad, 2), '0'), '.') }}
                                @endif
                            </td>
                            <td class="vb"></td>
                            <td class="obs">{{ $detalle?->observaciones ?? '' }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <table class="sign-labels">
                <tr>
                    <td>Firma del Solicitante:</td>
                    <td>Firma del Responsable:</td>
                    <td>Autorizado por:</td>
                </tr>
            </table>
            <table class="signatures">
                <tr>
                    <td>V°B°<br><strong>{{ strtoupper($receptor) }}</strong></td>
                    <td>V°B°<br><strong>{{ strtoupper($responsable) }}</strong></td>
                    <td>V°B°</td>
                </tr>
            </table>

            <div class="note">
                Cualquier daño, deterioro, perdida u otro se responsabiliza el personal a cargo de los materiales, equipos y otros sujeto a descuento, salvo estos cumplan su ciclo de vida.
            </div>
        </div>
    </div>
</body>
</html>
