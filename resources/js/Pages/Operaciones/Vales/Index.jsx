import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { todayInLima } from '@/utils/date';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, ChevronDown, Edit2, Eye, FileText, Plus, Send, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const today = todayInLima();
const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-200';

export default function Index({ auth, vales = [], productos = [], almacenes = [], centros = [], trabajadores = [] }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [detailVale, setDetailVale] = useState(null);
    const [editing, setEditing] = useState(null);
    const [showProductForm, setShowProductForm] = useState(false);
    const [productSearch, setProductSearch] = useState('');
    const [workerSearch, setWorkerSearch] = useState('');
    const [productDraft, setProductDraft] = useState({ producto_id: '', cantidad: '1', observaciones: '' });
    const [valeToDeliver, setValeToDeliver] = useState(null);
    const valeRows = vales.data ?? vales;

    const { data, setData, post, processing, errors, reset } = useForm({
        almacen_id: '',
        centro_costo_id: '',
        trabajador_id: '',
        fecha: today,
        motivo: '',
        observaciones: '',
        detalles: [],
    });

    const selectedWorker = useMemo(
        () => trabajadores.find((trabajador) => Number(trabajador.id) === Number(data.trabajador_id)),
        [trabajadores, data.trabajador_id],
    );

    const filteredWorkers = useMemo(() => {
        const term = workerSearch.trim().toLowerCase();
        return trabajadores
            .filter((trabajador) => {
                const name = `${trabajador.nombres ?? ''} ${trabajador.apellidos ?? ''}`.toLowerCase();
                const dni = `${trabajador.dni ?? ''}`.toLowerCase();
                return !term || name.includes(term) || dni.includes(term);
            })
            .slice(0, 5);
    }, [trabajadores, workerSearch]);

    const filteredProducts = useMemo(() => {
        const term = productSearch.trim().toLowerCase();
        return productos
            .filter((producto) => `${producto.nombre ?? ''}`.toLowerCase().includes(term))
            .slice(0, 5);
    }, [productos, productSearch]);

    const openCreate = () => {
        setEditing(null);
        reset();
        setData({
            almacen_id: almacenes[0]?.id ? String(almacenes[0].id) : '',
            centro_costo_id: centros[0]?.id ? String(centros[0].id) : '',
            trabajador_id: '',
            fecha: today,
            motivo: '',
            observaciones: '',
            detalles: [],
        });
        setWorkerSearch('');
        setProductSearch('');
        setProductDraft({ producto_id: '', cantidad: '1', observaciones: '' });
        setShowProductForm(false);
        setModalOpen(true);
    };

    const openEdit = (vale) => {
        const trabajadorRelacionado = [vale.trabajador?.nombres, vale.trabajador?.apellidos].filter(Boolean).join(' ') || vale.trabajador?.nombre || '';
        const trabajadorNombre = vale.receptor_nombre ?? (trabajadorRelacionado || vale.destino || '');
        const trabajadorEncontrado = trabajadores.find((trabajador) => {
            const nombre = `${trabajador.nombres ?? ''} ${trabajador.apellidos ?? ''}`.trim().toLowerCase();
            const dni = `${trabajador.dni ?? ''}`.trim();
            return (
                Number(trabajador.id) === Number(vale.trabajador_id)
                || (trabajadorNombre && nombre === trabajadorNombre.trim().toLowerCase())
                || (vale.receptor_dni && dni === String(vale.receptor_dni))
            );
        });

        setEditing(vale);
        setData({
            almacen_id: vale.almacen_id ? String(vale.almacen_id) : '',
            centro_costo_id: vale.centro_costo_id ? String(vale.centro_costo_id) : '',
            trabajador_id: trabajadorEncontrado?.id ? String(trabajadorEncontrado.id) : '',
            fecha: String(vale.fecha ?? today).slice(0, 10),
            motivo: vale.motivo ?? '',
            observaciones: vale.observaciones ?? '',
            detalles: (vale.detalles ?? []).map((detalle) => ({
                producto_id: detalle.producto_id,
                producto_nombre: detalle.producto?.nombre ?? '',
                cantidad: detalle.cantidad,
                observaciones: detalle.observaciones ?? null,
            })),
        });
        setWorkerSearch(trabajadorNombre);
        setProductSearch('');
        setProductDraft({ producto_id: '', cantidad: '1', observaciones: '' });
        setShowProductForm(false);
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
        reset();
    };

    const addProduct = () => {
        if (!productDraft.producto_id || Number(productDraft.cantidad) <= 0) {
            return;
        }

        const producto = productos.find((item) => Number(item.id) === Number(productDraft.producto_id));
        if (!producto) {
            return;
        }

        setData('detalles', [
            ...data.detalles,
            {
                producto_id: producto.id,
                producto_nombre: producto.nombre,
                cantidad: productDraft.cantidad,
                observaciones: productDraft.observaciones || null,
            },
        ]);

        setProductDraft({ producto_id: '', cantidad: '1', observaciones: '' });
        setProductSearch('');
        setShowProductForm(false);
    };

    const removeProduct = (index) => {
        setData('detalles', data.detalles.filter((_, itemIndex) => itemIndex !== index));
    };

    const submit = (event) => {
        event.preventDefault();
        const resolvedWorker = data.trabajador_id
            ? null
            : trabajadores.find((trabajador) => {
                const nombre = `${trabajador.nombres ?? ''} ${trabajador.apellidos ?? ''}`.trim().toLowerCase();
                return nombre === workerSearch.trim().toLowerCase();
            });
        const payload = {
            ...data,
            trabajador_id: data.trabajador_id || resolvedWorker?.id || '',
            receptor_nombre: selectedWorker
                ? `${selectedWorker.nombres ?? ''} ${selectedWorker.apellidos ?? ''}`.trim()
                : workerSearch.trim(),
            receptor_dni: selectedWorker?.dni ?? '',
        };
        const options = {
            preserveScroll: true,
            onSuccess: closeModal,
        };

        if (editing) {
            router.put(`/operaciones/vales/${editing.id}`, payload, options);
            return;
        }

        router.post('/operaciones/vales', payload, options);
    };

    const deliver = (vale) => {
        setValeToDeliver(vale);
    };

    const confirmDeliver = () => {
        if (!valeToDeliver) {
            return;
        }

        router.post(`/operaciones/vales/${valeToDeliver.id}/entregar`, {}, {
            preserveScroll: true,
            onFinish: () => setValeToDeliver(null),
        });
    };

    const receptorNombre = (vale) => {
        const trabajadorNombre = [vale.trabajador?.nombres, vale.trabajador?.apellidos].filter(Boolean).join(' ');
        return vale.receptor_nombre ?? (trabajadorNombre || '-');
    };

    return (
        <AuthenticatedLayout user={auth?.user} header={<Header />}>
            <Head title="Vales de Salida" />

            <section className="p-6">
                <div className="rounded-md border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <h2 className="text-xl font-bold text-slate-950">Vales de Salida</h2>
                            <p className="mt-1 text-sm text-slate-500">Entrega controlada de productos desde almacen.</p>
                        </div>
                        <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 rounded-md bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                            <Plus className="h-4 w-4" />
                            Nuevo
                        </button>
                    </div>

                    <div className="p-6">
                        <div className="overflow-hidden rounded-md border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <Th>Numero</Th>
                                        <Th>Fecha</Th>
                                        <Th>Almacen</Th>
                                        <Th>Receptor</Th>
                                        <Th>Items</Th>
                                        <Th>Estado</Th>
                                        <Th className="text-right">Acciones</Th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {valeRows.length === 0 ? (
                                        <tr>
                                            <td colSpan="7" className="px-6 py-10 text-center text-slate-500">No hay vales registrados</td>
                                        </tr>
                                    ) : (
                                        valeRows.map((vale) => (
                                            <tr key={vale.id}>
                                                <Td>{vale.numero}</Td>
                                                <Td>{String(vale.fecha ?? '').slice(0, 10)}</Td>
                                                <Td>{vale.almacen?.nombre ?? '-'}</Td>
                                                <Td>{receptorNombre(vale)}</Td>
                                                <Td>{vale.detalles?.length ?? 0}</Td>
                                                <Td>
                                                    <span className={`rounded-full px-3 py-1 text-xs font-semibold ${vale.estado === 'entregado' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'}`}>
                                                        {vale.estado === 'entregado' ? 'ENTREGADO' : 'PENDIENTE'}
                                                    </span>
                                                </Td>
                                                <Td className="text-right">
                                                    <button type="button" onClick={() => setDetailVale(vale)} title="Ver detalle" className="mr-2 inline-flex h-9 w-9 items-center justify-center rounded-md border border-sky-200 text-sky-600 hover:bg-sky-50">
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                    {vale.estado === 'pendiente' && (
                                                        <>
                                                            <button type="button" onClick={() => openEdit(vale)} title="Editar" className="mr-2 inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50">
                                                                <Edit2 className="h-4 w-4" />
                                                            </button>
                                                            <button type="button" onClick={() => deliver(vale)} title="Entregar" className="mr-2 inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 hover:bg-emerald-50">
                                                                <Check className="h-4 w-4" />
                                                            </button>
                                                        </>
                                                    )}
                                                    <a href={`/operaciones/vales/${vale.id}/pdf`} target="_blank" title="PDF" className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50">
                                                        <FileText className="h-4 w-4" />
                                                    </a>
                                                </Td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={vales} />
                    </div>
                </div>
            </section>

            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 px-4">
                    <form onSubmit={submit} className="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-md bg-white shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                            <h3 className="text-xl font-bold text-slate-800">{editing ? 'Editar Vale de Salida' : 'Nuevo Vale de Salida'}</h3>
                            <button type="button" onClick={closeModal} className="rounded-full border border-slate-400 p-3 text-slate-600 hover:bg-slate-100">
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <div className="max-h-[70vh] overflow-y-auto px-6 py-5">
                            <div className="grid gap-4 md:grid-cols-4">
                                <Field label="Almacen *" error={errors.almacen_id}>
                                    <select value={data.almacen_id} onChange={(e) => setData('almacen_id', e.target.value)} className={inputClass}>
                                        <option value="">Seleccione</option>
                                        {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                                    </select>
                                </Field>
                                <Field label="Obra / Unidad" error={errors.centro_costo_id}>
                                    <select value={data.centro_costo_id} onChange={(e) => setData('centro_costo_id', e.target.value)} className={inputClass}>
                                        <option value="">Sin centro</option>
                                        {centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.nombre}</option>)}
                                    </select>
                                </Field>
                                <Field label="Fecha *" error={errors.fecha}>
                                    <input type="date" value={data.fecha} onChange={(e) => setData('fecha', e.target.value)} className={inputClass} />
                                </Field>
                            </div>

                            <div className="mt-4 grid gap-4 md:grid-cols-3">
                                <Field label="Receptor *" error={errors.trabajador_id}>
                                    <div className="relative">
                                        <input
                                            value={workerSearch}
                                            onChange={(e) => {
                                                setWorkerSearch(e.target.value);
                                                setData('trabajador_id', '');
                                            }}
                                            placeholder="Buscar trabajador o usuario..."
                                            className={`${inputClass} pr-10`}
                                        />
                                        <ChevronDown className="pointer-events-none absolute right-3 top-3 h-5 w-5 text-slate-500" />
                                        {workerSearch && !data.trabajador_id && (
                                            <div className="absolute z-20 mt-1 max-h-56 w-full overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                                                {filteredWorkers.map((trabajador) => (
                                                    <button
                                                        key={trabajador.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setData('trabajador_id', trabajador.id);
                                                            setWorkerSearch(`${trabajador.nombres ?? ''} ${trabajador.apellidos ?? ''}`.trim());
                                                        }}
                                                        className="block w-full px-4 py-2 text-left text-sm hover:bg-slate-50"
                                                    >
                                                        {`${trabajador.nombres ?? ''} ${trabajador.apellidos ?? ''}`.trim()} <span className="text-slate-400">{trabajador.dni}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </Field>
                                <Field label="DNI Receptor">
                                    <input value={selectedWorker?.dni ?? ''} readOnly placeholder="Autocompletado" className={`${inputClass} bg-slate-50 text-slate-500`} />
                                </Field>
                            </div>

                            <Field label="Motivo" error={errors.motivo} className="mt-4">
                                <textarea value={data.motivo} onChange={(e) => setData('motivo', e.target.value)} placeholder="Motivo de la salida..." className={`${inputClass} min-h-20`} />
                            </Field>

                            <div className="mt-5 border-t border-slate-200 pt-5">
                                <div className="mb-3 flex items-center justify-between">
                                    <h4 className="font-bold text-slate-700">Productos *</h4>
                                    <button type="button" onClick={() => setShowProductForm(true)} className="inline-flex items-center gap-2 rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                                        <Plus className="h-4 w-4" />
                                        Agregar
                                    </button>
                                </div>

                                {showProductForm && (
                                    <div className="mb-4 rounded-md border border-slate-800/70 p-4">
                                        <div className="grid gap-4 md:grid-cols-[1fr_160px_1fr_auto]">
                                            <Field label="Producto">
                                                <div className="relative">
                                                    <input value={productSearch} onChange={(e) => setProductSearch(e.target.value)} placeholder="Buscar producto..." className={inputClass} />
                                                    {productSearch && !productDraft.producto_id && (
                                                        <div className="absolute z-20 mt-1 max-h-56 w-full overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                                                            {filteredProducts.map((producto) => (
                                                                <button
                                                                    key={producto.id}
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setProductDraft((draft) => ({ ...draft, producto_id: producto.id }));
                                                                        setProductSearch(producto.nombre);
                                                                    }}
                                                                    className="block w-full px-4 py-2 text-left text-sm hover:bg-slate-50"
                                                                >
                                                                    {producto.nombre}
                                                                </button>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </Field>
                                            <Field label="Cantidad">
                                                <input type="number" min="0.0001" step="0.0001" value={productDraft.cantidad} onChange={(e) => setProductDraft((draft) => ({ ...draft, cantidad: e.target.value }))} className={inputClass} />
                                            </Field>
                                            <Field label="Observaciones">
                                                <input value={productDraft.observaciones} onChange={(e) => setProductDraft((draft) => ({ ...draft, observaciones: e.target.value }))} className={inputClass} />
                                            </Field>
                                            <div className="flex items-end">
                                                <button type="button" onClick={addProduct} className="rounded-md bg-slate-800 px-4 py-2.5 font-semibold text-white">
                                                    Agregar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="overflow-hidden rounded-md border border-slate-800/70">
                                    <table className="min-w-full">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <Th>Producto</Th>
                                                <Th>Cantidad</Th>
                                                <Th>Observaciones</Th>
                                                <Th className="text-right">Acciones</Th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.detalles.length === 0 ? (
                                                <tr>
                                                    <td colSpan="4" className="px-6 py-10 text-center text-slate-500">No hay productos agregados</td>
                                                </tr>
                                            ) : (
                                                data.detalles.map((detalle, index) => (
                                                    <tr key={`${detalle.producto_id}-${index}`} className="border-t border-slate-100">
                                                        <Td>{detalle.producto_nombre}</Td>
                                                        <Td>{Number(detalle.cantidad).toFixed(2)}</Td>
                                                        <Td>{detalle.observaciones || '-'}</Td>
                                                        <Td className="text-right">
                                                            <button type="button" onClick={() => removeProduct(index)} title="Quitar" className="text-red-600 hover:text-red-700">
                                                                <Trash2 className="h-5 w-5" />
                                                            </button>
                                                        </Td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                                {errors.detalles && <p className="mt-2 text-sm text-red-600">{errors.detalles}</p>}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                            <button type="button" onClick={closeModal} className="rounded-md bg-slate-100 px-5 py-2.5 font-semibold text-slate-700">Cancelar</button>
                            <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-slate-800 px-5 py-2.5 font-semibold text-white disabled:opacity-60">
                                <Send className="h-4 w-4" />
                                {editing ? 'Actualizar Vale' : 'Registrar Vale'}
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {detailVale && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 px-4">
                    <div className="w-full max-w-3xl overflow-hidden rounded-md bg-white shadow-2xl">
                        <div className="flex items-center justify-between px-6 py-5">
                            <h3 className="text-xl font-bold text-slate-700">Detalle del Vale</h3>
                            <button type="button" onClick={() => setDetailVale(null)} className="rounded-full border border-slate-500 p-3 text-slate-600 hover:bg-slate-100">
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <div className="px-6 pb-6">
                            <div className="grid grid-cols-4 gap-4 bg-slate-50 px-5 py-4">
                                <DetailMetric label="Numero" value={detailVale.numero} strong />
                                <DetailMetric
                                    label="Estado"
                                    value={detailVale.estado === 'entregado' ? 'ENTREGADO' : 'PENDIENTE'}
                                    badge={detailVale.estado === 'entregado' ? 'success' : 'pending'}
                                />
                                <DetailMetric label="Fecha" value={String(detailVale.fecha ?? '').slice(0, 10)} />
                                <DetailMetric label="Almacen" value={detailVale.almacen?.nombre ?? '-'} />
                            </div>

                            <div className="mt-3 grid grid-cols-2 gap-6">
                                <div>
                                    <p className="text-sm text-slate-500">Receptor</p>
                                    <p className="text-base font-semibold text-slate-700">{receptorNombre(detailVale)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-slate-500">Obra / Unidad</p>
                                    <p className="text-base font-semibold text-slate-700">{detailVale.centroCosto?.nombre ?? detailVale.centro_costo?.nombre ?? '-'}</p>
                                </div>
                            </div>

                            <div className="mt-5 overflow-hidden border-t border-slate-200">
                                <table className="min-w-full">
                                    <thead>
                                        <tr className="border-b border-slate-200">
                                            <th className="px-2 py-3 text-left font-bold text-slate-700">Codigo</th>
                                            <th className="px-2 py-3 text-left font-bold text-slate-700">Producto</th>
                                            <th className="px-2 py-3 text-right font-bold text-slate-700">Solicitado</th>
                                            <th className="px-2 py-3 text-right font-bold text-slate-700">Entregado</th>
                                            <th className="px-2 py-3 text-right font-bold text-slate-700">Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(detailVale.detalles ?? []).map((detalle, index) => (
                                            <tr key={`${detalle.id ?? detalle.producto_id}-${index}`} className="border-b border-slate-200 even:bg-slate-50">
                                                <td className="px-2 py-2 text-slate-700">{detalle.producto?.codigo ?? '-'}</td>
                                                <td className="px-2 py-2 text-slate-700">{detalle.producto?.nombre ?? detalle.producto_nombre ?? '-'}</td>
                                                <td className="px-2 py-2 text-right text-slate-700">{Number(detalle.cantidad ?? 0).toFixed(2)}</td>
                                                <td className="px-2 py-2 text-right font-semibold text-emerald-600">{detailVale.estado === 'entregado' ? Number(detalle.cantidad ?? 0).toFixed(2) : '-'}</td>
                                                <td className="px-2 py-2 text-right text-slate-700">S/ 0.00</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-5 text-right text-lg font-bold text-slate-700">Total: S/ 0.00</div>

                            <div className="mt-6 flex justify-end">
                                <button type="button" onClick={() => setDetailVale(null)} className="rounded-md bg-slate-100 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-200">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <ConfirmDialog
                show={Boolean(valeToDeliver)}
                title="Entregar vale"
                message={valeToDeliver ? `Se registrara la entrega del vale ${valeToDeliver.numero}. Esta accion actualizara el estado del documento.` : ''}
                confirmText="Entregar"
                tone="success"
                onConfirm={confirmDeliver}
                onCancel={() => setValeToDeliver(null)}
            />
        </AuthenticatedLayout>
    );
}

function Header() {
    return (
        <div>
            <h1 className="text-2xl font-bold text-slate-950">Vales de Salida</h1>
            <p className="text-sm text-slate-500">Logistica</p>
        </div>
    );
}

function Field({ label, error, children, className = '' }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-sm font-semibold text-slate-700">{label}</span>
            {children}
            {error && <span className="mt-1 block text-sm text-red-600">{error}</span>}
        </label>
    );
}

function Th({ children, className = '' }) {
    return <th className={`px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 ${className}`}>{children}</th>;
}

function Td({ children, className = '' }) {
    return <td className={`px-5 py-4 text-sm text-slate-800 ${className}`}>{children}</td>;
}

function DetailMetric({ label, value, strong = false, badge = null }) {
    return (
        <div>
            <p className="text-sm text-slate-500">{label}</p>
            {badge ? (
                <span className={`mt-1 inline-block rounded-md px-3 py-1 text-sm font-bold ${badge === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
                    {value}
                </span>
            ) : (
                <p className={`mt-1 text-base ${strong ? 'font-bold text-blue-700' : 'font-semibold text-slate-700'}`}>{value}</p>
            )}
        </div>
    );
}
