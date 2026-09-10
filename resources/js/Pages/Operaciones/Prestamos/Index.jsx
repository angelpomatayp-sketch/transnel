import FlashMessage from '@/Components/Admin/FlashMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { todayInLima } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Eye, FileText, Plus, RotateCcw, Send, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const today = todayInLima();

function trabajadorNombre(trabajador) {
    return trabajador?.nombre ?? [trabajador?.nombres, trabajador?.apellidos].filter(Boolean).join(' ') ?? '-';
}

function formatNumber(value) {
    return Number(value ?? 0).toFixed(2);
}

function formatDate(value) {
    if (!value) return '-';
    return String(value).slice(0, 10);
}

function estadoClass(estado) {
    const styles = {
        prestado: 'bg-amber-100 text-amber-700',
        parcial: 'bg-sky-100 text-sky-700',
        devuelto: 'bg-emerald-100 text-emerald-700',
        anulado: 'bg-red-100 text-red-700',
    };

    return styles[estado] ?? 'bg-slate-100 text-slate-700';
}

function IconButton({ title, children, className = '', ...props }) {
    return (
        <button
            type="button"
            title={title}
            className={`inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}

function Modal({ title, children, onClose, footer, maxWidth = 'max-w-5xl' }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
            <div className={`flex max-h-[92vh] w-full ${maxWidth} flex-col overflow-hidden rounded-lg bg-white shadow-2xl`}>
                <div className="flex items-center justify-between border-b border-slate-200 px-8 py-5">
                    <h2 className="text-2xl font-bold text-slate-800">{title}</h2>
                    <button type="button" onClick={onClose} className="rounded-full border border-slate-300 p-3 text-slate-600 hover:bg-slate-50" title="Cerrar">
                        <X className="h-5 w-5" />
                    </button>
                </div>
                <div className="overflow-y-auto px-8 py-6">{children}</div>
                {footer && <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-8 py-5">{footer}</div>}
            </div>
        </div>
    );
}

function ProductSelect({ productos, value, onChange }) {
    return (
        <select value={value} onChange={(event) => onChange(event.target.value)} className="w-full rounded-lg border border-slate-300 px-4 py-3">
            <option value="">Seleccionar</option>
            {productos.map((producto) => (
                <option key={producto.id} value={producto.id}>
                    {producto.nombre}
                </option>
            ))}
        </select>
    );
}

function PrestamoForm({ title, productos, trabajadores, almacenes, centros, initial, onClose }) {
    const [form, setForm] = useState(initial ?? {
        trabajador_id: '',
        almacen_id: '',
        centro_costo_id: '',
        fecha_prestamo: today,
        fecha_devolucion_programada: '',
        observaciones: '',
        detalles: [],
    });
    const [item, setItem] = useState({ producto_id: '', cantidad: 1, observaciones: '' });

    const addItem = () => {
        if (!item.producto_id || Number(item.cantidad) <= 0) return;
        const producto = productos.find((row) => Number(row.id) === Number(item.producto_id));
        setForm((current) => ({
            ...current,
            detalles: [
                ...current.detalles,
                {
                    producto_id: Number(item.producto_id),
                    producto,
                    cantidad: Number(item.cantidad),
                    observaciones: item.observaciones || null,
                },
            ],
        }));
        setItem({ producto_id: '', cantidad: 1, observaciones: '' });
    };

    const submit = (event) => {
        event.preventDefault();
        router.post('/operaciones/prestamos', form, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title={title}
                onClose={onClose}
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <Send className="h-5 w-5" /> Registrar prestamo
                        </button>
                    </>
                }
            >
                <div className="grid gap-4 md:grid-cols-3">
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Trabajador *</span>
                        <select value={form.trabajador_id} onChange={(event) => setForm({ ...form, trabajador_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Seleccionar</option>
                            {trabajadores.map((trabajador) => (
                                <option key={trabajador.id} value={trabajador.id}>{trabajadorNombre(trabajador)}</option>
                            ))}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Almacen *</span>
                        <select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Seleccionar</option>
                            {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Fecha *</span>
                        <input type="date" value={form.fecha_prestamo} onChange={(event) => setForm({ ...form, fecha_prestamo: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Obra / Unidad</span>
                        <select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Sin centro</option>
                            {centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.nombre}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Devolucion programada</span>
                        <input type="date" value={form.fecha_devolucion_programada} onChange={(event) => setForm({ ...form, fecha_devolucion_programada: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                </div>

                <label className="mt-4 block space-y-2">
                    <span className="font-semibold text-slate-700">Observaciones</span>
                    <textarea value={form.observaciones ?? ''} onChange={(event) => setForm({ ...form, observaciones: event.target.value })} className="h-20 w-full rounded-lg border border-slate-300 px-4 py-3" />
                </label>

                <div className="mt-6 rounded-lg border border-slate-800 p-5">
                    <div className="mb-4 text-lg font-bold text-slate-700">Herramientas / equipos</div>
                    <div className="grid gap-3 md:grid-cols-[1fr_160px_1fr_auto]">
                        <ProductSelect productos={productos} value={item.producto_id} onChange={(value) => setItem({ ...item, producto_id: value })} />
                        <input type="number" min="0.01" step="0.01" value={item.cantidad} onChange={(event) => setItem({ ...item, cantidad: event.target.value })} className="rounded-lg border border-slate-300 px-4 py-3" />
                        <input value={item.observaciones ?? ''} onChange={(event) => setItem({ ...item, observaciones: event.target.value })} placeholder="Observaciones" className="rounded-lg border border-slate-300 px-4 py-3" />
                        <button type="button" onClick={addItem} className="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">
                            <Plus className="h-5 w-5" /> Agregar
                        </button>
                    </div>
                </div>

                <ItemsTable detalles={form.detalles} remove={(index) => setForm((current) => ({ ...current, detalles: current.detalles.filter((_, itemIndex) => itemIndex !== index) }))} />
            </Modal>
        </form>
    );
}

function ItemsTable({ detalles, remove }) {
    return (
        <div className="mt-5 overflow-hidden rounded-lg border border-slate-300">
            <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                    <tr>
                        <th className="px-5 py-4">Producto</th>
                        <th className="px-5 py-4">Cantidad</th>
                        <th className="px-5 py-4">Devuelto</th>
                        <th className="px-5 py-4">Pendiente</th>
                        <th className="px-5 py-4">Observaciones</th>
                        {remove && <th className="px-5 py-4 text-right">Acciones</th>}
                    </tr>
                </thead>
                <tbody>
                    {detalles.length === 0 && (
                        <tr>
                            <td colSpan={remove ? 6 : 5} className="px-5 py-8 text-center text-slate-400">No hay productos agregados</td>
                        </tr>
                    )}
                    {detalles.map((detalle, index) => (
                        <tr key={`${detalle.producto_id}-${index}`} className="border-t border-slate-200">
                            <td className="px-5 py-4">{detalle.producto?.nombre ?? '-'}</td>
                            <td className="px-5 py-4">{formatNumber(detalle.cantidad)}</td>
                            <td className="px-5 py-4">{formatNumber(detalle.cantidad_devuelta)}</td>
                            <td className="px-5 py-4">{formatNumber(detalle.cantidad_pendiente ?? Number(detalle.cantidad) - Number(detalle.cantidad_devuelta ?? 0))}</td>
                            <td className="px-5 py-4">{detalle.observaciones ?? '-'}</td>
                            {remove && (
                                <td className="px-5 py-4 text-right">
                                    <button type="button" onClick={() => remove(index)} className="text-red-600">Quitar</button>
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function ReturnModal({ prestamo, onClose }) {
    const [form, setForm] = useState({
        motivo_devolucion: '',
        detalles: (prestamo.detalles ?? []).map((detalle) => ({
            id: detalle.id,
            producto: detalle.producto,
            cantidad: detalle.cantidad,
            cantidad_devuelta_actual: detalle.cantidad_devuelta ?? 0,
            cantidad_pendiente: detalle.cantidad_pendiente ?? Math.max(0, Number(detalle.cantidad) - Number(detalle.cantidad_devuelta ?? 0)),
            cantidad_devuelta: detalle.cantidad_pendiente ?? Math.max(0, Number(detalle.cantidad) - Number(detalle.cantidad_devuelta ?? 0)),
        })),
    });

    const submit = (event) => {
        event.preventDefault();
        router.put(`/operaciones/prestamos/${prestamo.id}/devolver`, {
            motivo: form.motivo_devolucion,
            motivo_devolucion: form.motivo_devolucion,
            fecha_devolucion: today,
            detalles: form.detalles.map((detalle) => ({
                id: detalle.id,
                cantidad: detalle.cantidad_devuelta,
            })),
        }, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title="Registrar devolucion"
                onClose={onClose}
                maxWidth="max-w-4xl"
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <RotateCcw className="h-5 w-5" /> Confirmar devolucion
                        </button>
                    </>
                }
            >
                <div className="overflow-hidden rounded-lg border border-slate-300">
                    <table className="w-full text-left">
                        <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                            <tr>
                                <th className="px-4 py-3">Producto</th>
                                <th className="px-4 py-3">Prestado</th>
                                <th className="px-4 py-3">Ya devuelto</th>
                                <th className="px-4 py-3">Pendiente</th>
                                <th className="px-4 py-3">Devuelve ahora</th>
                            </tr>
                        </thead>
                        <tbody>
                            {form.detalles.map((detalle, index) => (
                                <tr key={detalle.id} className="border-t border-slate-200">
                                    <td className="px-4 py-3">{detalle.producto?.nombre}</td>
                                    <td className="px-4 py-3">{formatNumber(detalle.cantidad)}</td>
                                    <td className="px-4 py-3">{formatNumber(detalle.cantidad_devuelta_actual)}</td>
                                    <td className="px-4 py-3">{formatNumber(detalle.cantidad_pendiente)}</td>
                                    <td className="px-4 py-3">
                                        <input
                                            type="number"
                                            min="0"
                                            max={detalle.cantidad_pendiente}
                                            step="0.01"
                                            value={detalle.cantidad_devuelta}
                                            onChange={(event) => setForm((current) => ({
                                                ...current,
                                                detalles: current.detalles.map((row, rowIndex) => rowIndex === index ? { ...row, cantidad_devuelta: event.target.value } : row),
                                            }))}
                                            className="w-32 rounded-lg border border-slate-300 px-3 py-2"
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <label className="mt-5 block space-y-2">
                    <span className="font-semibold text-slate-700">Motivo / observacion de devolucion</span>
                    <textarea value={form.motivo_devolucion} onChange={(event) => setForm({ ...form, motivo_devolucion: event.target.value })} className="h-24 w-full rounded-lg border border-slate-300 px-4 py-3" />
                </label>
            </Modal>
        </form>
    );
}

function DetailModal({ prestamo, onClose }) {
    return (
        <Modal title="Detalle del prestamo" onClose={onClose} maxWidth="max-w-4xl" footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cerrar</button>}>
            <div className="grid gap-4 rounded-lg bg-slate-50 p-5 md:grid-cols-4">
                <div><div className="text-sm text-slate-500">Codigo</div><div className="font-semibold text-blue-700">{prestamo.codigo}</div></div>
                <div><div className="text-sm text-slate-500">Trabajador</div><div className="font-semibold">{trabajadorNombre(prestamo.trabajador)}</div></div>
                <div><div className="text-sm text-slate-500">Almacen</div><div className="font-semibold">{prestamo.almacen?.nombre}</div></div>
                <div><div className="text-sm text-slate-500">Estado</div><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(prestamo.estado)}`}>{prestamo.estado}</span></div>
            </div>
            <ItemsTable detalles={prestamo.detalles ?? []} />
        </Modal>
    );
}

export default function Index({ prestamos = [], productos = [], trabajadores = [], almacenes = [], centros = [] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [detailOpen, setDetailOpen] = useState(null);
    const [returnOpen, setReturnOpen] = useState(null);
    const prestamoRows = prestamos.data ?? prestamos;
    const activePrestamos = useMemo(() => prestamoRows, [prestamoRows]);

    return (
        <AuthenticatedLayout headerTitle="Prestamos" headerSubtitle="Logistica">
            <Head title="Prestamos" />

            <div className="px-3 py-6 xl:px-5">
                <FlashMessage />

                <section className="mx-auto w-full max-w-none overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-6 py-6">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Prestamos</h1>
                            <p className="mt-2 text-slate-500">Prestamo y devolucion parcial de herramientas y equipos.</p>
                        </div>
                        <button onClick={() => setCreateOpen(true)} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <Plus className="h-5 w-5" /> Nuevo prestamo
                        </button>
                    </div>

                    <div className="p-6">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                                    <tr>
                                        <th className="w-[15%] px-4 py-4">Codigo</th>
                                        <th className="w-[22%] px-4 py-4">Trabajador</th>
                                        <th className="w-[16%] px-4 py-4">Almacen</th>
                                        <th className="w-[10%] px-4 py-4">Prestamo</th>
                                        <th className="w-[10%] px-4 py-4">Devolucion</th>
                                        <th className="w-[6%] px-4 py-4 text-center">Items</th>
                                        <th className="w-[10%] px-4 py-4">Estado</th>
                                        <th className="w-[11%] px-4 py-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {activePrestamos.map((prestamo) => (
                                        <tr key={prestamo.id} className="border-t border-slate-200">
                                            <td className="break-words px-4 py-5 font-mono text-sm">{prestamo.codigo}</td>
                                            <td className="px-4 py-5">
                                                <div className="font-semibold">{trabajadorNombre(prestamo.trabajador)}</div>
                                                <div className="text-sm text-slate-500">DNI: {prestamo.trabajador?.dni ?? '-'}</div>
                                            </td>
                                            <td className="px-4 py-5">{prestamo.almacen?.nombre ?? '-'}</td>
                                            <td className="whitespace-nowrap px-4 py-5">{formatDate(prestamo.fecha_prestamo)}</td>
                                            <td className="whitespace-nowrap px-4 py-5">{formatDate(prestamo.fecha_devolucion)}</td>
                                            <td className="px-4 py-5 text-center">{prestamo.detalles?.length ?? 0}</td>
                                            <td className="px-4 py-5">
                                                <span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(prestamo.estado)}`}>{prestamo.estado}</span>
                                            </td>
                                            <td className="px-4 py-5">
                                                <div className="flex justify-end gap-2">
                                                    <IconButton title="Ver detalle" onClick={() => setDetailOpen(prestamo)}><Eye className="h-5 w-5 text-blue-600" /></IconButton>
                                                    {['prestado', 'parcial'].includes(prestamo.estado) && (
                                                        <IconButton title="Registrar devolucion" onClick={() => setReturnOpen(prestamo)}><RotateCcw className="h-5 w-5 text-emerald-600" /></IconButton>
                                                    )}
                                                    <a title="PDF" href={`/operaciones/prestamos/${prestamo.id}/pdf`} target="_blank" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200 bg-white text-blue-600 transition hover:bg-blue-50">
                                                        <FileText className="h-5 w-5" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {activePrestamos.length === 0 && (
                                        <tr>
                                            <td colSpan="8" className="px-5 py-10 text-center text-slate-400">No hay prestamos registrados</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={prestamos} />
                    </div>
                </section>
            </div>

            {createOpen && (
                <PrestamoForm
                    title="Nuevo prestamo"
                    productos={productos}
                    trabajadores={trabajadores}
                    almacenes={almacenes}
                    centros={centros}
                    onClose={() => setCreateOpen(false)}
                />
            )}
            {detailOpen && <DetailModal prestamo={detailOpen} onClose={() => setDetailOpen(null)} />}
            {returnOpen && <ReturnModal prestamo={returnOpen} onClose={() => setReturnOpen(null)} />}
        </AuthenticatedLayout>
    );
}

