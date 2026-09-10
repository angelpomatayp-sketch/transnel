import { ActionButton, AdminCard, SelectField, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { todayInLima } from '@/utils/date';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { ArrowDown, ArrowDownToLine, ArrowRight, ArrowUp, ArrowUpFromLine, Ban, ClipboardCheck, Download, Eye, Plus, Search, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const today = todayInLima();

function ProductSearchField({ label = 'Producto', products, value, onChange, error }) {
    const selected = products.find((producto) => String(producto.id) === String(value));
    const [query, setQuery] = useState(selected?.nombre ?? '');
    const [open, setOpen] = useState(false);

    useEffect(() => {
        setQuery(selected?.nombre ?? '');
    }, [selected?.id]);

    const filtered = useMemo(() => {
        const search = query.trim().toLowerCase();

        if (!search) {
            return products.slice(0, 8);
        }

        return products
            .filter((producto) => `${producto.nombre} ${producto.codigo}`.toLowerCase().includes(search))
            .slice(0, 5);
    }, [products, query]);

    return (
        <label className="relative block">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span>
            <input
                type="text"
                value={query}
                onChange={(event) => {
                    setQuery(event.target.value);
                    onChange('');
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 120)}
                className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                placeholder="Buscar producto..."
                autoComplete="off"
            />
            {open && (
                <div className="absolute z-40 mt-1 max-h-52 w-full overflow-hidden rounded-md border border-slate-300 bg-white py-1 text-sm shadow-lg">
                    {filtered.length === 0 && (
                        <div className="px-3 py-2 text-slate-400">Sin resultados</div>
                    )}
                    {filtered.map((producto) => (
                        <button
                            key={producto.id}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onChange(producto.id);
                                setQuery(producto.nombre);
                                setOpen(false);
                            }}
                            className="block w-full px-3 py-2 text-left text-slate-700 hover:bg-sky-50 hover:text-sky-700"
                        >
                            {producto.nombre}
                        </button>
                    ))}
                </div>
            )}
            {error && <p className="mt-1 text-xs font-medium text-red-600">{error}</p>}
        </label>
    );
}

export default function Index({ movimientos, filters = {}, productos, almacenes, centros }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState('');
    const [importStatus, setImportStatus] = useState('');
    const [canceling, setCanceling] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [cancelReason, setCancelReason] = useState('');
    const [cancelError, setCancelError] = useState('');
    const [filterData, setFilterData] = useState({
        buscar: filters.buscar ?? '',
        tipo: filters.tipo ?? '',
        estado: filters.estado ?? '',
        desde: filters.desde ?? '',
        hasta: filters.hasta ?? '',
    });
    const [draft, setDraft] = useState({ producto_id: '', cantidad: '1', costo_unitario: '0', lote: '', vencimiento: '' });
    const [draftError, setDraftError] = useState('');
    const importRef = useRef(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        tipo: 'ENTRADA',
        almacen_origen_id: '',
        almacen_destino_id: '',
        centro_costo_id: '',
        fecha: today,
        documento: '',
        observaciones: '',
        detalles: [],
    });
    const adjustmentForm = useForm({
        almacen_id: '',
        producto_id: '',
        stock_fisico: '',
        costo_unitario: '',
        fecha: today,
        motivo: '',
    });

    const movementRows = movimientos.data ?? movimientos;
    const movementLinks = movimientos.links ?? [];

    function emptyDraft(type = data.tipo) {
        return { producto_id: '', cantidad: '1', costo_unitario: type === 'ENTRADA' ? '0' : '', lote: '', vencimiento: '' };
    }

    function selectedProduct(productId) {
        return productos.find((producto) => String(producto.id) === String(productId));
    }

    function setDraftField(field, value) {
        setDraft((current) => ({ ...current, [field]: value }));
        setDraftError('');
    }

    function addDetalle() {
        if (!draft.producto_id) {
            setDraftError('Seleccione un producto.');
            return;
        }

        if (Number(draft.cantidad) <= 0) {
            setDraftError('La cantidad debe ser mayor a cero.');
            return;
        }

        if (requiresCost && Number(draft.costo_unitario) < 0) {
            setDraftError('El costo unitario no puede ser negativo.');
            return;
        }

        setData('detalles', [...data.detalles, draft]);
        setDraft(emptyDraft());
    }

    function removeDetalle(index) {
        setData('detalles', data.detalles.filter((_, current) => current !== index));
    }

    function openMovement(type) {
        reset();
        setData({
            tipo: type,
            almacen_origen_id: '',
            almacen_destino_id: '',
            centro_costo_id: '',
            fecha: today,
            documento: '',
            observaciones: '',
            detalles: [],
        });
        setDraft(emptyDraft(type));
        setDraftError('');
        setModalOpen(true);
    }

    function openAdjustment() {
        adjustmentForm.reset();
        adjustmentForm.clearErrors();
        adjustmentForm.setData({
            almacen_id: '',
            producto_id: '',
            stock_fisico: '',
            costo_unitario: '',
            fecha: today,
            motivo: '',
        });
        setAdjustOpen(true);
    }

    function setFilter(field, value) {
        setFilterData((current) => ({ ...current, [field]: value }));
    }

    function applyFilters(e) {
        e.preventDefault();
        router.get(route('inventario.movimientos.index'), filterData, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function clearFilters() {
        setFilterData({ buscar: '', tipo: '', estado: '', desde: '', hasta: '' });
        router.get(route('inventario.movimientos.index'), {}, {
            preserveScroll: true,
            replace: true,
        });
    }

    function changeType(type) {
        setData({
            ...data,
            tipo: type,
            almacen_origen_id: ['SALIDA', 'TRANSFERENCIA', 'AJUSTE_SALIDA'].includes(type) ? data.almacen_origen_id : '',
            almacen_destino_id: ['ENTRADA', 'TRANSFERENCIA', 'AJUSTE_ENTRADA'].includes(type) ? data.almacen_destino_id : '',
            detalles: data.detalles.map((detalle) => ({
                ...detalle,
                costo_unitario: ['ENTRADA', 'AJUSTE_ENTRADA'].includes(type) ? (detalle.costo_unitario || '0') : '',
            })),
        });
        setDraft(emptyDraft(type));
        setDraftError('');
    }

    function submit(e) {
        e.preventDefault();
        if (data.detalles.length === 0) {
            setDraftError('Agregue al menos un producto.');
            return;
        }

        post(route('inventario.movimientos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setDraft(emptyDraft());
                setDraftError('');
                setModalOpen(false);
            },
        });
    }

    function submitAdjustment(e) {
        e.preventDefault();

        adjustmentForm.post(route('inventario.movimientos.adjust'), {
            preserveScroll: true,
            onSuccess: () => {
                adjustmentForm.reset();
                setAdjustOpen(false);
            },
        });
    }

    async function importExcel(file) {
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('tipo', data.tipo);
        setImporting(true);
        setImportError('');
        setImportStatus('');

        try {
            const response = await axios.post(route('inventario.movimientos.import-items'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const importedItems = response.data.items ?? [];
            const importErrors = response.data.errors ?? [];

            setData('detalles', [...data.detalles, ...importedItems]);
            setImportError(importErrors.length ? importErrors.join(' ') : '');
            setImportStatus(importedItems.length > 0 ? `${importedItems.length} producto(s) agregados al movimiento.` : '');
        } catch (error) {
            setImportError(error.response?.data?.message ?? 'No se pudo importar el archivo.');
        } finally {
            setImporting(false);
            if (importRef.current) {
                importRef.current.value = '';
            }
        }
    }

    function openCancel(row) {
        setCanceling(row);
        setCancelReason('');
        setCancelError('');
    }

    function submitCancel(e) {
        e.preventDefault();

        if (cancelReason.trim().length < 5) {
            setCancelError('Ingrese un motivo de al menos 5 caracteres.');
            return;
        }

        router.post(route('inventario.movimientos.cancel', canceling.id), {
            motivo: cancelReason,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setCanceling(null);
                setCancelReason('');
                setCancelError('');
            },
            onError: (errors) => setCancelError(errors.motivo ?? 'No se pudo anular el movimiento.'),
        });
    }

    function formatMoney(value) {
        return `S/ ${Number(value || 0).toFixed(2)}`;
    }

    function formatQuantity(value) {
        return Number(value || 0).toFixed(2);
    }

    function movementTypeBadge(type) {
        if (String(type).includes('ENTRADA')) {
            return 'bg-emerald-100 text-emerald-700';
        }

        if (String(type).includes('SALIDA')) {
            return 'bg-red-100 text-red-700';
        }

        return 'bg-sky-100 text-sky-700';
    }

    function movementStatus(row) {
        return row.estado === 'anulado' ? 'ANULADO' : 'COMPLETADO';
    }

    function movementStatusBadge(row) {
        return row.estado === 'anulado' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700';
    }

    function movementWarehouse(row) {
        if (String(row.tipo).includes('SALIDA')) {
            return row.almacen_origen?.nombre ?? '-';
        }

        if (String(row.tipo).includes('ENTRADA')) {
            return row.almacen_destino?.nombre ?? '-';
        }

        return row.almacen_destino?.nombre ?? row.almacen_origen?.nombre ?? '-';
    }

    function movementDirectionIcon(row) {
        if (String(row.tipo).includes('SALIDA')) {
            return <ArrowRight className="h-4 w-4 text-red-500" />;
        }

        if (String(row.tipo).includes('ENTRADA')) {
            return <ArrowDown className="h-4 w-4 text-emerald-600" />;
        }

        return <ArrowUp className="h-4 w-4 text-sky-600" />;
    }

    const requiresOrigen = ['SALIDA', 'TRANSFERENCIA', 'AJUSTE_SALIDA'].includes(data.tipo);
    const requiresDestino = ['ENTRADA', 'TRANSFERENCIA', 'AJUSTE_ENTRADA'].includes(data.tipo);
    const requiresCost = ['ENTRADA', 'AJUSTE_ENTRADA'].includes(data.tipo);

    return (
        <AuthenticatedLayout title="Movimientos">
            <Head title="Movimientos" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminCard
                    title="Movimientos confirmados"
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <ActionButton type="button" tone="secondary" onClick={() => openMovement('ENTRADA')}>
                                <ArrowDownToLine className="mr-2 h-4 w-4" /> Entrada
                            </ActionButton>
                            <ActionButton type="button" tone="secondary" onClick={() => openMovement('SALIDA')}>
                                <ArrowUpFromLine className="mr-2 h-4 w-4" /> Salida
                            </ActionButton>
                            <ActionButton type="button" tone="secondary" onClick={openAdjustment}>
                                <ClipboardCheck className="mr-2 h-4 w-4" /> Reajuste
                            </ActionButton>
                        </div>
                    }
                >
                    <form onSubmit={applyFilters} className="mb-5 grid gap-3 xl:grid-cols-[1.7fr_0.75fr_0.75fr_1fr_1fr_auto]">
                        <label className="relative block">
                            <Search className="pointer-events-none absolute left-3 top-[2.25rem] h-4 w-4 text-slate-400" />
                            <span className="sr-only">Buscar</span>
                            <input
                                type="text"
                                value={filterData.buscar}
                                onChange={(event) => setFilter('buscar', event.target.value)}
                                className="mt-6 h-10 w-full rounded-md border border-slate-300 pl-9 pr-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                placeholder="Buscar por numero o documento..."
                            />
                        </label>
                        <label className="block">
                            <span className="sr-only">Tipo</span>
                            <select value={filterData.tipo} onChange={(event) => setFilter('tipo', event.target.value)} className="mt-6 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100">
                                <option value="">Tipo</option>
                                <option value="ENTRADA">Entrada</option>
                                <option value="SALIDA">Salida</option>
                                <option value="AJUSTE_ENTRADA">Ajuste entrada</option>
                                <option value="AJUSTE_SALIDA">Ajuste salida</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </label>
                        <label className="block">
                            <span className="sr-only">Estado</span>
                            <select value={filterData.estado} onChange={(event) => setFilter('estado', event.target.value)} className="mt-6 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100">
                                <option value="">Estado</option>
                                <option value="confirmado">Completado</option>
                                <option value="anulado">Anulado</option>
                            </select>
                        </label>
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Desde</span>
                            <input type="date" value={filterData.desde} onChange={(event) => setFilter('desde', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100" />
                        </label>
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Hasta</span>
                            <input type="date" value={filterData.hasta} onChange={(event) => setFilter('hasta', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100" />
                        </label>
                        <div className="mt-6 flex gap-2">
                            <button type="button" onClick={clearFilters} className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:bg-slate-50" title="Limpiar filtros" aria-label="Limpiar filtros">
                                <X className="h-4 w-4" />
                            </button>
                            <button type="submit" className="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-700 text-white hover:bg-brand-800" title="Buscar" aria-label="Buscar">
                                <Search className="h-4 w-4" />
                            </button>
                        </div>
                    </form>

                    <div className="overflow-hidden rounded-lg border border-slate-200">
                        <div className="overflow-x-auto">
                            <table className="min-w-[1120px] table-fixed divide-y divide-slate-200 text-sm">
                                <thead className="bg-white">
                                    <tr>
                                        <th className="w-44 px-4 py-3 text-left font-bold text-slate-900">Numero</th>
                                        <th className="w-32 px-4 py-3 text-left font-bold text-slate-900">Tipo</th>
                                        <th className="w-32 px-4 py-3 text-left font-bold text-slate-900">Fecha</th>
                                        <th className="w-56 px-4 py-3 text-left font-bold text-slate-900">Almacen</th>
                                        <th className="w-20 px-4 py-3 text-center font-bold text-slate-900">Items</th>
                                        <th className="w-44 px-4 py-3 text-left font-bold text-slate-900">Doc. Ref.</th>
                                        <th className="w-36 px-4 py-3 text-left font-bold text-slate-900">Estado</th>
                                        <th className="w-28 px-4 py-3 text-right font-bold text-slate-900">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {movementRows.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-8 text-center text-slate-500">
                                                Sin movimientos para los filtros seleccionados.
                                            </td>
                                        </tr>
                                    )}
                                    {movementRows.map((row) => (
                                        <tr key={row.id} className="odd:bg-white even:bg-slate-50">
                                            <td className="truncate whitespace-nowrap px-4 py-4 font-mono text-slate-800" title={row.numero}>{row.numero}</td>
                                            <td className="whitespace-nowrap px-4 py-4">
                                                <span className={`inline-flex rounded-md px-2 py-1 text-xs font-bold ${movementTypeBadge(row.tipo)}`}>
                                                    {String(row.tipo).replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-4 text-slate-800">{row.fecha?.substring(0, 10) ?? '-'}</td>
                                            <td className="truncate whitespace-nowrap px-4 py-4 text-slate-800" title={movementWarehouse(row)}>
                                                <span className="inline-flex items-center gap-2">
                                                    {movementDirectionIcon(row)}
                                                    <span className="truncate">{movementWarehouse(row)}</span>
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-4 text-center font-semibold text-slate-800">{row.detalles?.length ?? 0}</td>
                                            <td className="truncate whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-800" title={row.documento || '-'}>
                                                {row.documento || '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-4">
                                                <span className={`inline-flex rounded-md px-2.5 py-1 text-xs font-bold ${movementStatusBadge(row)}`}>
                                                    {movementStatus(row)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setViewing(row)}
                                                        className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-600 transition hover:bg-sky-50"
                                                        title="Ver detalle"
                                                        aria-label="Ver detalle"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                    {row.estado !== 'anulado' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openCancel(row)}
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-500 transition hover:bg-red-50"
                                                            title="Anular movimiento"
                                                            aria-label="Anular movimiento"
                                                        >
                                                            <Ban className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {movementLinks.length > 3 && (
                        <div className="mt-4 flex flex-wrap justify-end gap-2">
                            {movementLinks.map((link, index) => (
                                <button
                                    key={`${link.label}-${index}`}
                                    type="button"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                    className={`h-9 min-w-9 rounded-md border px-3 text-sm font-semibold ${
                                        link.active
                                            ? 'border-brand-700 bg-brand-700 text-white'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </AdminCard>
                <AdminModal show={modalOpen} onClose={() => !importOpen && setModalOpen(false)} title={data.tipo === 'SALIDA' ? 'Nueva salida' : 'Nueva entrada'} description="Los movimientos se confirman inmediatamente y actualizan stock/kardex." formId="movimiento-form" submitLabel="Confirmar movimiento" processing={processing} maxWidth="4xl">
                    <form id="movimiento-form" onSubmit={submit} className="space-y-5">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <SelectField label="Tipo" value={data.tipo} onChange={(e) => changeType(e.target.value)} error={errors.tipo} disabled>
                                <option value={data.tipo}>
                                    {data.tipo === 'SALIDA' ? 'Salida' : 'Entrada'}
                                </option>
                            </SelectField>
                            {data.tipo === 'SALIDA' && (
                                <SelectField label="Almacen destino" value={data.almacen_origen_id} onChange={(e) => setData('almacen_origen_id', e.target.value)} error={errors.almacen_origen_id}>
                                    <option value="">Seleccionar</option>
                                    {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                                </SelectField>
                            )}
                            {requiresOrigen && data.tipo !== 'SALIDA' && (
                                <SelectField label="Almacen origen" value={data.almacen_origen_id} onChange={(e) => setData('almacen_origen_id', e.target.value)} error={errors.almacen_origen_id}>
                                    <option value="">Seleccionar</option>
                                    {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                                </SelectField>
                            )}
                            {requiresDestino && (
                                <SelectField label="Almacen destino" value={data.almacen_destino_id} onChange={(e) => setData('almacen_destino_id', e.target.value)} error={errors.almacen_destino_id}>
                                    <option value="">Seleccionar</option>
                                    {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                                </SelectField>
                            )}
                            <SelectField label="Centro de costo" value={data.centro_costo_id} onChange={(e) => setData('centro_costo_id', e.target.value)} error={errors.centro_costo_id}>
                                <option value="">Sin centro</option>
                                {centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.nombre}</option>)}
                            </SelectField>
                            <TextField label="Fecha" type="date" value={data.fecha} onChange={(e) => setData('fecha', e.target.value)} error={errors.fecha} />
                            <TextField label="Documento" value={data.documento} onChange={(e) => setData('documento', e.target.value)} error={errors.documento} placeholder="Ej: FAC-001, GR-123" />
                            <div className="md:col-span-2">
                                <TextField label="Observaciones" value={data.observaciones} onChange={(e) => setData('observaciones', e.target.value)} error={errors.observaciones} />
                            </div>
                        </div>

                        <div className="rounded-lg border border-brand-700 bg-slate-50 p-4">
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <h3 className="text-base font-bold text-slate-700">Agregar Productos</h3>
                                <button type="button" onClick={() => setImportOpen(true)} className="inline-flex h-9 items-center rounded-md border border-emerald-200 bg-emerald-50 px-3 text-sm font-semibold text-emerald-600 hover:bg-emerald-100">
                                    Importar desde Excel
                                </button>
                            </div>

                            <div className={`grid gap-3 ${requiresCost ? 'md:grid-cols-[1.4fr_1fr_1fr_1fr_1fr]' : 'md:grid-cols-[1.6fr_1fr_1fr_1fr]'}`}>
                                <ProductSearchField
                                    products={productos}
                                    value={draft.producto_id}
                                    onChange={(productId) => setDraftField('producto_id', productId)}
                                />
                                <TextField label="Cantidad" type="number" step="0.0001" value={draft.cantidad} onChange={(e) => setDraftField('cantidad', e.target.value)} />
                                {requiresCost && (
                                    <TextField label="Costo unit." type="number" step="0.0001" value={draft.costo_unitario} onChange={(e) => setDraftField('costo_unitario', e.target.value)} placeholder="S/ 0.00" />
                                )}
                                <TextField label="Lote" value={draft.lote} onChange={(e) => setDraftField('lote', e.target.value)} />
                                <TextField label="Vencimiento" type="date" value={draft.vencimiento} onChange={(e) => setDraftField('vencimiento', e.target.value)} />
                            </div>

                            <div className="mt-3 flex flex-wrap items-center gap-3">
                                <ActionButton type="button" tone="secondary" onClick={addDetalle}>
                                    <Plus className="mr-2 h-4 w-4" /> Agregar
                                </ActionButton>
                                {(draftError || errors.detalles) && (
                                    <p className="text-sm font-medium text-red-600">{draftError || errors.detalles}</p>
                                )}
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg border border-brand-700">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-100">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-bold text-slate-700">Codigo</th>
                                        <th className="px-4 py-3 text-left font-bold text-slate-700">Producto</th>
                                        <th className="px-4 py-3 text-left font-bold text-slate-700">Cantidad</th>
                                        {requiresCost && <th className="px-4 py-3 text-left font-bold text-slate-700">Costo Unit.</th>}
                                        {requiresCost && <th className="px-4 py-3 text-left font-bold text-slate-700">Subtotal</th>}
                                        <th className="px-4 py-3 text-right font-bold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {data.detalles.length === 0 && (
                                        <tr>
                                            <td className="px-4 py-12 text-center text-slate-400" colSpan={requiresCost ? 6 : 4}>
                                                No hay productos agregados
                                            </td>
                                        </tr>
                                    )}
                                    {data.detalles.map((detalle, index) => {
                                        const producto = selectedProduct(detalle.producto_id);
                                        const subtotal = Number(detalle.cantidad) * Number(detalle.costo_unitario || 0);

                                        return (
                                            <tr key={`${detalle.producto_id}-${index}`}>
                                                <td className="px-4 py-3 text-slate-700">{producto?.codigo ?? '-'}</td>
                                                <td className="px-4 py-3 text-slate-700">{producto?.nombre ?? '-'}</td>
                                                <td className="px-4 py-3 text-slate-700">{Number(detalle.cantidad).toFixed(2)}</td>
                                                {requiresCost && <td className="px-4 py-3 text-slate-700">S/ {Number(detalle.costo_unitario || 0).toFixed(2)}</td>}
                                                {requiresCost && <td className="px-4 py-3 text-slate-700">S/ {subtotal.toFixed(2)}</td>}
                                                <td className="px-4 py-3 text-right">
                                                    <ActionButton type="button" tone="secondary" onClick={() => removeDetalle(index)}>Quitar</ActionButton>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            {data.detalles.map((detalle, index) => (
                                <div key={`errors-${index}`} className="hidden">
                                    <SelectField label="Producto" value={detalle.producto_id} error={errors[`detalles.${index}.producto_id`]}>
                                        <option value="">Seleccionar</option>
                                    </SelectField>
                                </div>
                            ))}
                        </div>

                    </form>
                </AdminModal>
                <AdminModal
                    show={adjustOpen}
                    onClose={() => setAdjustOpen(false)}
                    title="Reajuste de inventario"
                    description="Registra el conteo fisico y el sistema genera el ajuste necesario."
                    formId="reajuste-form"
                    submitLabel="Registrar reajuste"
                    processing={adjustmentForm.processing}
                    maxWidth="2xl"
                >
                    <form id="reajuste-form" onSubmit={submitAdjustment} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SelectField
                                label="Almacen"
                                value={adjustmentForm.data.almacen_id}
                                onChange={(e) => adjustmentForm.setData('almacen_id', e.target.value)}
                                error={adjustmentForm.errors.almacen_id}
                            >
                                <option value="">Seleccionar</option>
                                {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                            </SelectField>
                            <TextField
                                label="Fecha"
                                type="date"
                                value={adjustmentForm.data.fecha}
                                onChange={(e) => adjustmentForm.setData('fecha', e.target.value)}
                                error={adjustmentForm.errors.fecha}
                            />
                            <div className="md:col-span-2">
                                <ProductSearchField
                                    label="Producto"
                                    products={productos}
                                    value={adjustmentForm.data.producto_id}
                                    onChange={(productId) => adjustmentForm.setData('producto_id', productId)}
                                    error={adjustmentForm.errors.producto_id}
                                />
                            </div>
                            <TextField
                                label="Stock fisico"
                                type="number"
                                min="0"
                                step="0.01"
                                value={adjustmentForm.data.stock_fisico}
                                onChange={(e) => adjustmentForm.setData('stock_fisico', e.target.value)}
                                error={adjustmentForm.errors.stock_fisico}
                                placeholder="Cantidad contada"
                            />
                            <TextField
                                label="Costo unitario"
                                type="number"
                                min="0"
                                step="0.01"
                                value={adjustmentForm.data.costo_unitario}
                                onChange={(e) => adjustmentForm.setData('costo_unitario', e.target.value)}
                                error={adjustmentForm.errors.costo_unitario}
                                placeholder="Solo si aumenta stock"
                            />
                        </div>
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Motivo</span>
                            <textarea
                                value={adjustmentForm.data.motivo}
                                onChange={(e) => adjustmentForm.setData('motivo', e.target.value)}
                                rows={3}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                placeholder="Ej: Conteo fisico, diferencia por inventario, regularizacion documentaria"
                            />
                            {adjustmentForm.errors.motivo && <p className="mt-1 text-xs font-medium text-red-600">{adjustmentForm.errors.motivo}</p>}
                        </label>
                        <div className="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                            Si el stock fisico es mayor al sistema se registrara un ajuste de entrada. Si es menor, un ajuste de salida.
                        </div>
                    </form>
                </AdminModal>
                {importOpen && (
                    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 px-4">
                        <div className="w-full max-w-3xl rounded-lg bg-white p-6 shadow-2xl" onClick={(event) => event.stopPropagation()}>
                            <div className="mb-5 flex items-start justify-between gap-4">
                                <h2 className="text-xl font-bold text-slate-700">Importar Productos desde Excel</h2>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setImportOpen(false);
                                        setImportError('');
                                        setImportStatus('');
                                    }}
                                    className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-50"
                                    aria-label="Cerrar importacion"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            <div className="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sky-800">
                                <p className="text-sm font-medium">
                                    Descarga la plantilla, llena los productos y sube el archivo. El sistema buscara cada producto por codigo o nombre.
                                </p>
                                <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <a
                                        href={route('inventario.movimientos.template')}
                                        className="inline-flex h-10 items-center justify-center rounded-md border border-sky-200 bg-white px-4 text-sm font-semibold text-sky-600 hover:bg-sky-50"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Descargar Plantilla
                                    </a>
                                    <input
                                        ref={importRef}
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        className="hidden"
                                        onChange={(event) => importExcel(event.target.files?.[0])}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => importRef.current?.click()}
                                        disabled={importing}
                                        className="inline-flex h-10 items-center justify-center rounded-md bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <Upload className="mr-2 h-4 w-4" />
                                        {importing ? 'Importando...' : 'Seleccionar archivo Excel'}
                                    </button>
                                </div>
                                {importError && (
                                    <p className="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                                        {importError}
                                    </p>
                                )}
                                {importStatus && (
                                    <p className="mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                        {importStatus}
                                    </p>
                                )}
                            </div>

                            <div className="mt-5 flex justify-end">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setImportOpen(false);
                                        setImportError('');
                                        setImportStatus('');
                                    }}
                                    className="inline-flex h-10 items-center justify-center rounded-md bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                )}
                <AdminModal show={Boolean(canceling)} onClose={() => setCanceling(null)} title="Anular movimiento" formId="cancelar-movimiento-form" submitLabel="Anular movimiento" maxWidth="lg">
                    <form id="cancelar-movimiento-form" onSubmit={submitCancel} className="space-y-4">
                        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            El movimiento no se eliminara. Se marcara como anulado y se revertira el stock asociado.
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-slate-700">
                                Movimiento: {canceling?.numero}
                            </p>
                            <label className="mt-3 block">
                                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Motivo de anulacion</span>
                                <textarea
                                    value={cancelReason}
                                    onChange={(e) => {
                                        setCancelReason(e.target.value);
                                        setCancelError('');
                                    }}
                                    rows={4}
                                    className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                    placeholder="Explique por que se anula este movimiento"
                                />
                            </label>
                            {cancelError && <p className="mt-2 text-sm font-medium text-red-600">{cancelError}</p>}
                        </div>
                    </form>
                </AdminModal>
                <AdminModal
                    show={Boolean(viewing)}
                    onClose={() => setViewing(null)}
                    title="Detalle del movimiento"
                    description={viewing?.numero}
                    maxWidth="4xl"
                >
                    <div className="space-y-5">
                        <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Tipo</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.tipo ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Fecha</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.fecha?.substring(0, 10) ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Origen</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.almacen_origen?.nombre ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Destino</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.almacen_destino?.nombre ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Usuario</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.usuario?.name ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-slate-500">Estado</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.estado === 'anulado' ? 'Anulado' : 'Confirmado'}</p>
                            </div>
                            <div className="md:col-span-2">
                                <p className="text-xs font-bold uppercase text-slate-500">Observaciones</p>
                                <p className="mt-1 font-semibold text-slate-800">{viewing?.observaciones || '-'}</p>
                            </div>
                            {viewing?.estado === 'anulado' && (
                                <div className="md:col-span-2 xl:col-span-4">
                                    <p className="text-xs font-bold uppercase text-slate-500">Motivo de anulacion</p>
                                    <p className="mt-1 font-semibold text-red-700">{viewing?.motivo_anulacion || '-'}</p>
                                </div>
                            )}
                        </div>

                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Codigo</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Producto</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Cantidad</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Costo Unit.</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Subtotal</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Lote</th>
                                        <th className="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {(viewing?.detalles ?? []).map((detalle) => (
                                        <tr key={detalle.id}>
                                            <td className="px-4 py-3 text-slate-700">{detalle.producto?.codigo ?? '-'}</td>
                                            <td className="px-4 py-3 text-slate-700">{detalle.producto?.nombre ?? '-'}</td>
                                            <td className="px-4 py-3 text-slate-700">{formatQuantity(detalle.cantidad)}</td>
                                            <td className="px-4 py-3 text-slate-700">{formatMoney(detalle.costo_unitario)}</td>
                                            <td className="px-4 py-3 text-slate-700">{formatMoney(Number(detalle.cantidad || 0) * Number(detalle.costo_unitario || 0))}</td>
                                            <td className="px-4 py-3 text-slate-700">{detalle.lote || '-'}</td>
                                            <td className="px-4 py-3 text-slate-700">{detalle.vencimiento || '-'}</td>
                                        </tr>
                                    ))}
                                    {(viewing?.detalles ?? []).length === 0 && (
                                        <tr>
                                            <td className="px-4 py-6 text-center text-slate-500" colSpan={7}>
                                                No hay productos registrados en este movimiento.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </AdminModal>
            </div>
        </AuthenticatedLayout>
    );
}


