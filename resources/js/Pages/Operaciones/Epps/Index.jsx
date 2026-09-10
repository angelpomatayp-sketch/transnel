import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { daysBetweenDates, todayInLima } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, History, Plus, RefreshCcw, RotateCcw, Send, ShieldCheck, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const today = todayInLima();

function trabajadorNombre(trabajador) {
    return trabajador?.nombre ?? [trabajador?.nombres, trabajador?.apellidos].filter(Boolean).join(' ') ?? '-';
}

function formatDate(value) {
    if (!value) return '-';
    const date = String(value).slice(0, 10);
    const [year, month, day] = date.split('-');
    return year && month && day ? `${day}/${month}/${year}` : date;
}

function estadoClass(estado, vence) {
    if (estado === 'devuelto') return 'bg-slate-100 text-slate-700';
    if (estado === 'renovado') return 'bg-sky-100 text-sky-700';
    if (estado === 'baja') return 'bg-red-100 text-red-700';
    if (estado === 'vencido') return 'bg-red-100 text-red-700';

    if (vence) {
        const diff = daysBetweenDates(today, vence);
        if (diff < 0) return 'bg-red-100 text-red-700';
        if (diff <= 30) return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
}

function estadoLabel(asignacion) {
    if (asignacion.estado && asignacion.estado !== 'entregado') return asignacion.estado;

    if (asignacion.fecha_vencimiento) {
        const diff = daysBetweenDates(today, asignacion.fecha_vencimiento);
        if (diff < 0) return 'vencido';
        if (diff <= 30) return 'por vencer';
    }

    return 'vigente';
}

function IconButton({ title, children, className = '', ...props }) {
    return (
        <button
            type="button"
            title={title}
            className={`inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 ${className}`}
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
                    <h2 className="flex items-center gap-3 text-2xl font-bold text-slate-800">
                        <ShieldCheck className="h-7 w-7 text-emerald-600" /> {title}
                    </h2>
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

function AssignModal({ productos, trabajadores, almacenes, centros, onClose }) {
    const [form, setForm] = useState({
        trabajador_id: '',
        producto_id: '',
        almacen_id: '',
        centro_costo_id: '',
        cantidad: 1,
        talla: '',
        fecha_entrega: today,
        observaciones: '',
    });

    const selectedTrabajador = trabajadores.find((row) => Number(row.id) === Number(form.trabajador_id));

    const submit = (event) => {
        event.preventDefault();
        router.post('/operaciones/epps', form, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title="Nueva asignacion de EPP"
                onClose={onClose}
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <Send className="h-5 w-5" /> Asignar EPP
                        </button>
                    </>
                }
            >
                <div className="grid gap-4 md:grid-cols-3">
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Trabajador *</span>
                        <select value={form.trabajador_id} onChange={(event) => setForm({ ...form, trabajador_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Seleccionar</option>
                            {trabajadores.map((trabajador) => <option key={trabajador.id} value={trabajador.id}>{trabajadorNombre(trabajador)}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">DNI</span>
                        <input readOnly value={selectedTrabajador?.dni ?? ''} placeholder="Autocompletado" className="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Fecha entrega *</span>
                        <input type="date" value={form.fecha_entrega} onChange={(event) => setForm({ ...form, fecha_entrega: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Producto EPP *</span>
                        <select value={form.producto_id} onChange={(event) => setForm({ ...form, producto_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Seleccionar</option>
                            {productos.map((producto) => <option key={producto.id} value={producto.id}>{producto.nombre}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Cantidad *</span>
                        <input type="number" min="0.01" step="0.01" value={form.cantidad} onChange={(event) => setForm({ ...form, cantidad: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Talla</span>
                        <input value={form.talla ?? ''} onChange={(event) => setForm({ ...form, talla: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Almacen *</span>
                        <select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Seleccionar</option>
                            {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Obra / Unidad</span>
                        <select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="">Sin centro</option>
                            {centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.nombre}</option>)}
                        </select>
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Observaciones</span>
                        <input value={form.observaciones ?? ''} onChange={(event) => setForm({ ...form, observaciones: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                </div>
            </Modal>
        </form>
    );
}

function ReturnModal({ asignacion, onClose }) {
    const [motivo, setMotivo] = useState('');

    const submit = (event) => {
        event.preventDefault();
        router.put(`/operaciones/epps/${asignacion.id}/devolver`, { motivo_devolucion: motivo }, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title="Devolver EPP"
                onClose={onClose}
                maxWidth="max-w-3xl"
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">Confirmar devolucion</button>
                    </>
                }
            >
                <div className="rounded-lg bg-slate-50 p-5">
                    <div className="font-semibold">{asignacion.producto?.nombre}</div>
                    <div className="text-slate-500">{trabajadorNombre(asignacion.trabajador)}</div>
                </div>
                <label className="mt-5 block space-y-2">
                    <span className="font-semibold text-slate-700">Motivo</span>
                    <textarea value={motivo} onChange={(event) => setMotivo(event.target.value)} className="h-24 w-full rounded-lg border border-slate-300 px-4 py-3" />
                </label>
            </Modal>
        </form>
    );
}

function HistoryModal({ trabajador, asignaciones, onClose }) {
    return (
        <Modal title="Historial de EPPs" onClose={onClose} maxWidth="max-w-5xl" footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cerrar</button>}>
            <h3 className="mb-6 text-xl font-bold text-slate-700">{trabajadorNombre(trabajador).toUpperCase()}</h3>
            <div className="overflow-hidden rounded-lg border border-slate-200">
                <table className="w-full text-left">
                    <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                        <tr>
                            <th className="px-5 py-4">EPP</th>
                            <th className="px-5 py-4">Cant.</th>
                            <th className="px-5 py-4">Entrega</th>
                            <th className="px-5 py-4">Vencimiento</th>
                            <th className="px-5 py-4">Estado</th>
                            <th className="px-5 py-4">Entregado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        {asignaciones.map((asignacion) => (
                            <tr key={asignacion.id} className="border-t border-slate-200">
                                <td className="px-5 py-4 font-semibold">{asignacion.producto?.nombre}</td>
                                <td className="px-5 py-4">{asignacion.cantidad}</td>
                                <td className="px-5 py-4">{formatDate(asignacion.fecha_entrega)}</td>
                                <td className="px-5 py-4">{formatDate(asignacion.fecha_vencimiento)}</td>
                                <td className="px-5 py-4">
                                    <span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(asignacion.estado, asignacion.fecha_vencimiento)}`}>{estadoLabel(asignacion)}</span>
                                </td>
                                <td className="px-5 py-4">{asignacion.entregado_por?.name ?? asignacion.usuario?.name ?? 'Administrador'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Modal>
    );
}

export default function Index({ asignaciones = [], productos = [], trabajadores = [], almacenes = [], centros = [] }) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [returnOpen, setReturnOpen] = useState(null);
    const [historyOpen, setHistoryOpen] = useState(null);
    const historial = useMemo(() => {
        if (!historyOpen?.trabajador?.id) return [];
        return asignaciones.filter((row) => Number(row.trabajador?.id) === Number(historyOpen.trabajador.id));
    }, [historyOpen, asignaciones]);

    return (
        <AuthenticatedLayout headerTitle="EPPs" headerSubtitle="Logistica">
            <Head title="EPPs" />

            <div className="px-6 py-8 xl:px-10">
                <FlashMessage />

                <section className="mx-auto w-full max-w-[98rem] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-8 py-7">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">EPPs</h1>
                            <p className="mt-2 text-slate-500">Asignacion, vencimiento y control de equipos de proteccion personal.</p>
                        </div>
                        <button onClick={() => setAssignOpen(true)} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <Plus className="h-5 w-5" /> Asignar EPP
                        </button>
                    </div>

                    <div className="p-8">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                                    <tr>
                                        <th className="w-[20%] px-4 py-4">Trabajador</th>
                                        <th className="w-[23%] px-4 py-4">EPP / Producto</th>
                                        <th className="w-[6%] px-4 py-4">Talla</th>
                                        <th className="w-[6%] px-4 py-4">Cant.</th>
                                        <th className="w-[10%] px-4 py-4">Entrega</th>
                                        <th className="w-[11%] px-4 py-4">Vencimiento</th>
                                        <th className="w-[10%] px-4 py-4">Estado</th>
                                        <th className="w-[6%] px-4 py-4">Confirm.</th>
                                        <th className="w-[8%] px-4 py-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {asignaciones.map((asignacion) => {
                                        const label = estadoLabel(asignacion);
                                        const active = ['entregado', 'vigente', null, undefined].includes(asignacion.estado) && !['devuelto', 'renovado', 'baja'].includes(asignacion.estado);

                                        return (
                                            <tr key={asignacion.id} className="border-t border-slate-200">
                                                <td className="px-4 py-5">
                                                    <div className="font-bold">{trabajadorNombre(asignacion.trabajador)}</div>
                                                    <div className="text-sm text-slate-500">DNI: {asignacion.trabajador?.dni ?? '-'}</div>
                                                </td>
                                                <td className="px-4 py-5">
                                                    <div className="font-bold">{asignacion.producto?.nombre}</div>
                                                    {asignacion.producto?.familia?.categoria_epp && <div className="mt-1 h-2 w-5 rounded-full bg-slate-100" title={asignacion.producto.familia.categoria_epp} />}
                                                </td>
                                                <td className="px-4 py-5">{asignacion.talla ?? '-'}</td>
                                                <td className="px-4 py-5">{asignacion.cantidad ?? 1}</td>
                                                <td className="px-4 py-5">{formatDate(asignacion.fecha_entrega)}</td>
                                                <td className="px-4 py-5">{formatDate(asignacion.fecha_vencimiento)}</td>
                                                <td className="px-4 py-5">
                                                    <span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(asignacion.estado, asignacion.fecha_vencimiento)}`}>{label}</span>
                                                </td>
                                                <td className="px-4 py-5"><CheckCircle2 className="h-6 w-6 text-emerald-500" /></td>
                                                <td className="px-4 py-5">
                                                    <div className="flex justify-end gap-2">
                                                        <IconButton title="Historial" onClick={() => setHistoryOpen(asignacion)}>
                                                            <History className="h-5 w-5 text-sky-600" />
                                                        </IconButton>
                                                        {active && (
                                                            <>
                                                                <IconButton title="Renovar" onClick={() => router.put(`/operaciones/epps/${asignacion.id}/renovar`, {}, { preserveScroll: true })}>
                                                                    <RefreshCcw className="h-5 w-5 text-amber-600" />
                                                                </IconButton>
                                                                <IconButton title="Devolver" onClick={() => setReturnOpen(asignacion)}>
                                                                    <RotateCcw className="h-5 w-5 text-blue-600" />
                                                                </IconButton>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {asignaciones.length === 0 && (
                                        <tr>
                                            <td colSpan="9" className="px-5 py-10 text-center text-slate-400">No hay EPPs asignados</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            {assignOpen && (
                <AssignModal
                    productos={productos}
                    trabajadores={trabajadores}
                    almacenes={almacenes}
                    centros={centros}
                    onClose={() => setAssignOpen(false)}
                />
            )}
            {returnOpen && <ReturnModal asignacion={returnOpen} onClose={() => setReturnOpen(null)} />}
            {historyOpen && <HistoryModal trabajador={historyOpen.trabajador} asignaciones={historial} onClose={() => setHistoryOpen(null)} />}
        </AuthenticatedLayout>
    );
}

