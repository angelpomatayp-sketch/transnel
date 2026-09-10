@php
    $logo = public_path('logo.jpeg');
    $fecha = optional($requerimiento->fecha)->format('d/m/Y') ?? optional($requerimiento->fecha_requerida)->format('d/m/Y') ?? now()->format('d/m/Y');
    $unidad = $requerimiento->centroCosto?->nombre ?? '-';
    $almacen = $requerimiento->almacen?->nombre ?? '-';
    $solicitante = $requerimiento->solicitante?->name ?? '-';
    $numero = $requerimiento->numero ?? '-';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 21px 21px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #001b44;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .format {
            width: 100%;
            table-layout: fixed;
            margin-top: 2px;
        }
        .format td {
            border: 1px solid #000;
            color: #000;
        }
        .logo-cell {
            width: 20%;
            height: 58px;
            text-align: center;
            vertical-align: middle;
        }
        .logo-cell img {
            width: 143px;
            height: 48px;
            object-fit: contain;
        }
        .title-cell {
            width: 60%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            height: 28px;
            vertical-align: middle;
        }
        .code-cell {
            width: 20%;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            line-height: 1.35;
            vertical-align: middle;
        }
        .number-inline {
            width: 225px;
            margin-left: auto;
            table-layout: fixed;
        }
        .number-inline td {
            border: 1px solid #000;
            height: 22px;
            text-align: center;
            font-weight: bold;
            color: #001b88;
            font-size: 9px;
        }
        .meta-wrap {
            margin-top: 10px;
            margin-bottom: 2px;
            height: 48px;
        }
        .meta-wrap td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
        }
        .meta {
            table-layout: fixed;
        }
        .meta td {
            border: 0;
            padding: 0 2px;
            vertical-align: bottom;
            font-size: 8px;
            font-weight: bold;
            color: #001b88;
        }
        .meta .label-left { width: 70px; }
        .meta .label-right { width: 90px; }
        .line {
            border-bottom: 1px solid #001b88 !important;
            color: #001b88;
            font-size: 9px;
            font-weight: bold;
            padding-left: 2px !important;
        }
        .info-area {
            position: relative;
            height: 62px;
            margin-top: 12px;
            margin-bottom: 0;
            color: #001b88;
            font-weight: bold;
        }
        .info-label {
            position: absolute;
            left: 0;
            width: 90px;
            font-size: 8px;
            line-height: 12px;
        }
        .info-left-line {
            position: absolute;
            left: 92px;
            width: 590px;
            height: 12px;
            border-bottom: 1px solid #001b88;
            padding-left: 3px;
            font-size: 9px;
            line-height: 12px;
        }
        .info-right-label {
            position: absolute;
            left: 740px;
            width: 90px;
            font-size: 8px;
            line-height: 12px;
        }
        .info-right-line {
            position: absolute;
            left: 830px;
            width: 230px;
            height: 12px;
            border-bottom: 1px solid #001b88;
            padding-left: 3px;
            font-size: 9px;
            line-height: 12px;
        }
        .info-number {
            position: absolute;
            right: 0;
            top: 0;
            width: 225px;
            border-collapse: collapse;
        }
        .info-number td {
            border: 1px solid #000;
            height: 22px;
            text-align: center;
            font-size: 9px;
            color: #001b88;
            font-weight: bold;
        }
        .info-left-line,
        .info-right-line,
        .info-number td:nth-child(2) {
            color: #000;
            font-weight: normal;
        }
        .items {
            width: 100%;
            table-layout: fixed;
            margin-top: 3px;
        }
        .items th,
        .items td {
            border: 1px solid #000;
        }
        .items th {
            height: 16px;
            padding: 2px;
            color: #001b88;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .items td {
            height: 58px;
            padding: 3px;
            color: #000;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.25;
        }
        .it { background: #fff; }
        .qty { background: #fff; }
        .items .qty-cell {
            background: #fff;
            font-weight: bold;
        }
        .sign-wrap {
            width: 84%;
            margin-left: 8%;
            margin-top: 20px;
        }
        .signatures-table {
            width: 100%;
            table-layout: fixed;
        }
        .signatures-table th {
            border: 0;
            color: #001b88;
            font-size: 6px;
            font-weight: bold;
            padding: 0 3px 4px 3px;
            text-align: left;
        }
        .signatures-table td {
            border: 1px solid #000;
            height: 70px;
            vertical-align: bottom;
            color: #001b88;
            font-size: 6px;
            padding: 3px;
        }
    </style>
</head>
<body>
    <table class="format">
        <colgroup>
            <col style="width: 20%;">
            <col style="width: 60%;">
            <col style="width: 20%;">
        </colgroup>
        <tr>
            <td class="logo-cell" style="width: 20%;" rowspan="2">
                @if (file_exists($logo))
                    <img src="{{ $logo }}" alt="Logo">
                @else
                    <strong>LOGISTICA</strong>
                @endif
            </td>
            <td class="title-cell" style="width: 60%;">FORMATO</td>
            <td class="code-cell" style="width: 20%;" rowspan="2">
                Codigo: FR-LOG-02<br>
                Version: 00<br>
                Fecha: 01/09/2026
            </td>
        </tr>
        <tr>
            <td class="title-cell">REQUERIMIENTO DE COMPRA Y/O SERVICIO</td>
        </tr>
    </table>

    <div class="info-area">
        <table class="info-number">
            <tr>
                <td style="width: 62px;">N°</td>
                <td>{{ $numero }}</td>
            </tr>
        </table>

        <div class="info-label" style="top: 28px;">Unidad / Obra:</div>
        <div class="info-left-line" style="top: 28px;">{{ mb_strtoupper($unidad) }}</div>
        <div class="info-label" style="top: 44px;">Referencia :</div>
        <div class="info-left-line" style="top: 44px;">{{ mb_strtoupper($requerimiento->motivo ?? '-') }}</div>

        <div class="info-right-label" style="top: 28px;">Lugar y fecha :</div>
        <div class="info-right-line" style="top: 28px;">{{ mb_strtoupper($almacen) }} &nbsp;&nbsp; {{ $fecha }}</div>
        <div class="info-right-label" style="top: 44px;">Solicitante :</div>
        <div class="info-right-line" style="top: 44px;">{{ mb_strtoupper($solicitante) }}</div>
    </div>

    <table class="items">
        <colgroup>
            <col style="width: 3%;">
            <col style="width: 4%;">
            <col style="width: 6%;">
            <col style="width: 45%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 20%;">
        </colgroup>
        <thead>
            <tr>
                <th class="it" style="width: 3%;" rowspan="2">IT.</th>
                <th class="qty" style="width: 4%;" rowspan="2">CANT.</th>
                <th class="unit" style="width: 6%;" rowspan="2">UNIDAD</th>
                <th class="description" style="width: 45%;" rowspan="2">DESCRIPCION</th>
                <th style="width: 22%;" colspan="3">RECEPCION DE PEDIDO</th>
                <th class="specifications" style="width: 20%;" rowspan="2">ESPECIFICACIONES</th>
            </tr>
            <tr>
                <th style="width: 7%;">CANT. APROB.</th>
                <th style="width: 7%;">PENDIENTE</th>
                <th style="width: 8%;">FECHA</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requerimiento->detalles as $index => $detalle)
                <tr>
                    <td style="width: 3%;">{{ $index + 1 }}</td>
                    <td class="qty-cell" style="width: 4%;">{{ rtrim(rtrim(number_format((float) $detalle->cantidad_solicitada, 2), '0'), '.') }}</td>
                    <td style="width: 6%;">{{ mb_strtoupper($detalle->producto?->unidad?->codigo ?? 'UND.') }}</td>
                    <td style="width: 45%;">{{ mb_strtoupper($detalle->producto?->nombre ?? '-') }}</td>
                    <td style="width: 7%;"></td>
                    <td style="width: 7%;"></td>
                    <td style="width: 8%;"></td>
                    <td style="width: 20%;">{{ mb_strtoupper($detalle->especificaciones ?? '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">SIN PRODUCTOS REGISTRADOS</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign-wrap">
        <table class="signatures-table">
            <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <th>Firma/Solicitante</th>
                <th>Aprobado por:</th>
                <th>Autorizado por Gerencia :</th>
                <th>Resp. Logistica</th>
            </tr>
            <tr>
                <td>V°B° &nbsp; {{ mb_strtoupper($solicitante) }}</td>
                <td>V°B°</td>
                <td>V°B°</td>
                <td>V°B°</td>
            </tr>
        </table>
    </div>
</body>
</html>
