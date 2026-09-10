@php
    $logoPath = public_path('logo.jpeg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;

    $fecha = $compra->fecha ?? $compra->created_at ?? now();
    $fechaTexto = $fecha instanceof \Carbon\Carbon
        ? $fecha->format('d/m/Y')
        : \Carbon\Carbon::parse($fecha)->format('d/m/Y');

    $entrega = $compra->fecha_entrega ?? $compra->fecha_requerida ?? $fecha;
    $entregaTexto = $entrega instanceof \Carbon\Carbon
        ? $entrega->format('d/m/Y')
        : \Carbon\Carbon::parse($entrega)->format('d/m/Y');

    $detalles = $compra->detalles ?? collect();
    $subtotal = $detalles->sum(fn ($detalle) => (float) ($detalle->subtotal ?? ((float) ($detalle->cantidad ?? 0) * (float) ($detalle->costo_unitario ?? 0))));
    $igv = (float) ($compra->igv ?? 0);
    $total = (float) ($compra->total ?? ($subtotal + $igv));
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 36px 48px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border: 1px solid #111827;
            height: 58px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 28%;
            text-align: center;
            background: #fff;
        }

        .logo {
            max-width: 112px;
            max-height: 42px;
            object-fit: contain;
        }

        .title-cell {
            width: 44%;
            text-align: center;
            color: #0b1f63;
            font-weight: 700;
        }

        .label-format {
            font-size: 8px;
            letter-spacing: 7px;
            margin-bottom: 7px;
        }

        .main-title {
            font-size: 14px;
            letter-spacing: 6px;
            white-space: nowrap;
        }

        .code-cell {
            width: 28%;
            text-align: center;
            line-height: 1.75;
            font-size: 8px;
        }

        .number {
            width: 35%;
            margin: 10px 0 8px auto;
            border: 1px solid #111827;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            padding: 4px 0;
        }

        .bar {
            margin-top: 7px;
            background: #485563;
            color: #fff;
            font-weight: 700;
            letter-spacing: 8px;
            text-align: center;
            padding: 4px 0;
        }

        .info td {
            border: 1px solid #9ca3af;
            height: 23px;
            padding: 4px 6px;
        }

        .info .label {
            width: 22%;
            background: #d9e9fb;
            color: #0b1f63;
            font-weight: 700;
        }

        .info .value {
            width: 28%;
            font-weight: 500;
        }

        .items {
            margin-top: 8px;
        }

        .items th {
            border: 1px solid #111827;
            background: #1f2f78;
            color: #fff;
            font-size: 7px;
            padding: 4px 3px;
            text-align: center;
        }

        .items td {
            border: 1px solid #9ca3af;
            height: 20px;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .totals {
            width: 34%;
            margin: 7px 0 0 auto;
        }

        .totals td {
            border: 1px solid #9ca3af;
            padding: 4px 6px;
        }

        .totals .label {
            background: #eef2f7;
            font-weight: 700;
        }

        .notes {
            margin-top: 7px;
            border: 1px solid #9ca3af;
            min-height: 28px;
            padding: 5px 6px;
        }

        .signatures {
            margin-top: 8px;
        }

        .signatures td {
            border: 1px solid #111827;
            height: 46px;
            width: 33.333%;
            vertical-align: bottom;
            padding: 4px 6px;
        }

        .sign-title {
            margin-bottom: 18px;
            color: #0b1f63;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                @else
                    <strong>LOGISTICA</strong>
                @endif
            </td>
            <td class="title-cell">
                <div class="label-format">FORMATO</div>
                <div class="main-title">ORDEN DE COMPRA</div>
            </td>
            <td class="code-cell">
                <div><strong>Codigo:</strong> FR-COM-01</div>
                <div><strong>Version:</strong> 00</div>
                <div><strong>Fecha:</strong> 01/09/2026</div>
            </td>
        </tr>
    </table>

    <div class="number">N° {{ $compra->numero }}</div>

    <div class="bar">DATOS DEL PROVEEDOR</div>
    <table class="info">
        <tr>
            <td class="label">RUC</td>
            <td class="value">{{ $compra->proveedor->ruc ?? '-' }}</td>
            <td class="label">Fecha emision</td>
            <td class="value">{{ $fechaTexto }}</td>
        </tr>
        <tr>
            <td class="label">Razon social</td>
            <td colspan="3" class="value">{{ $compra->proveedor->razon_social ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Contacto</td>
            <td class="value">{{ $compra->proveedor->contacto ?? '-' }}</td>
            <td class="label">Telefono</td>
            <td class="value">{{ $compra->proveedor->telefono ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Direccion</td>
            <td colspan="3" class="value">{{ $compra->proveedor->direccion ?? '-' }}</td>
        </tr>
    </table>

    <div class="bar">DATOS DE ENTREGA</div>
    <table class="info">
        <tr>
            <td class="label">Almacen destino</td>
            <td class="value">{{ $compra->almacen->nombre ?? '-' }}</td>
            <td class="label">Fecha entrega</td>
            <td class="value">{{ $entregaTexto }}</td>
        </tr>
        <tr>
            <td class="label">Obra / Unidad</td>
            <td class="value">{{ $compra->centroCosto->nombre ?? '-' }}</td>
            <td class="label">Req. referencia</td>
            <td class="value">{{ $compra->requerimiento->numero ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Estado</td>
            <td class="value">{{ strtoupper($compra->estado ?? '-') }}</td>
            <td class="label">Moneda</td>
            <td class="value">{{ $compra->moneda ?? 'S/' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">IT</th>
                <th style="width: 11%;">CODIGO</th>
                <th style="width: 38%;">DESCRIPCION</th>
                <th style="width: 9%;">UNIDAD</th>
                <th style="width: 10%;">CANT.</th>
                <th style="width: 12%;">C. UNIT.</th>
                <th style="width: 15%;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalles as $detalle)
                @php
                    $cantidad = (float) ($detalle->cantidad ?? 0);
                    $costo = (float) ($detalle->costo_unitario ?? 0);
                    $lineaSubtotal = (float) ($detalle->subtotal ?? ($cantidad * $costo));
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ $detalle->producto->codigo ?? '-' }}</td>
                    <td>{{ $detalle->producto->nombre ?? $detalle->descripcion ?? '-' }}</td>
                    <td class="center">{{ $detalle->producto->unidad->abreviatura ?? $detalle->producto->unidad->codigo ?? 'UND' }}</td>
                    <td class="center">{{ number_format($cantidad, 2) }}</td>
                    <td class="right">S/ {{ number_format($costo, 2) }}</td>
                    <td class="right">S/ {{ number_format($lineaSubtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center">Sin productos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="right">S/ {{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">IGV</td>
            <td class="right">S/ {{ number_format($igv, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total</td>
            <td class="right"><strong>S/ {{ number_format($total, 2) }}</strong></td>
        </tr>
    </table>

    <div class="notes">
        <strong>Observaciones:</strong> {{ $compra->observaciones ?? '-' }}
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-title">Solicitado por:</div>
                V°B° {{ $compra->usuario->name ?? auth()->user()->name ?? '' }}
            </td>
            <td>
                <div class="sign-title">Aprobado por:</div>
                V°B°
            </td>
            <td>
                <div class="sign-title">Proveedor:</div>
                V°B°
            </td>
        </tr>
    </table>
</body>
</html>
