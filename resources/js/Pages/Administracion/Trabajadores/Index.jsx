import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { daysBetweenDates, todayInLima } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { ChevronDown, Edit2, FileText, History, Plus, RefreshCcw, RotateCcw, Search, Send, ShieldCheck, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const today = todayInLima();

function nombreTrabajador(trabajador) {
    return trabajador?.nombre ?? [trabajador?.nombres, trabajador?.apellidos].filter(Boolean).join(' ') ?? '-';
}

function formatDate(value) {
    if (!value) return '-';
    const date = String(value).slice(0, 10);
    const [year, month, day] = date.split('-');
    return year && month && day ? `${day}/${month}/${year}` : date;
}

function estadoEpp(asignacion) {
    if (asignacion.estado && asignacion.estado !== 'entregado') return asignacion.estado;

    if (asignacion.fecha_vencimiento) {
        const diff = daysBetweenDates(today, asignacion.fecha_vencimiento);
        if (diff < 0) return 'vencido';
        if (diff <= 30) return 'por vencer';
    }

    return 'vigente';
}

function estadoClass(estado) {
    const styles = {
        activo: 'bg-emerald-100 text-emerald-700',
        inactivo: 'bg-slate-100 text-slate-600',
        vigente: 'bg-emerald-100 text-emerald-700',
        entregado: 'bg-emerald-100 text-emerald-700',
        'por vencer': 'bg-amber-100 text-amber-700',
        vencido: 'bg-red-100 text-red-700',
        devuelto: 'bg-slate-100 text-slate-700',
        renovado: 'bg-sky-100 text-sky-700',
        baja: 'bg-red-100 text-red-700',
    };

    return styles[estado] ?? 'bg-slate-100 text-slate-700';
}

function IconButton({ title, children, className = '', ...props }) {
    return (
        <button
            type="button"
            title={title}
            className={`inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}

function Modal({ title, children, onClose, footer, maxWidth = 'max-w-5xl', compact = false }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
            <div className={`flex max-h-[92vh] w-full ${maxWidth} flex-col overflow-hidden rounded-lg bg-white shadow-2xl`}>
                <div className={`flex items-center justify-between border-b border-slate-200 ${compact ? 'px-6 py-3' : 'px-8 py-5'}`}>
                    <h2 className={`${compact ? 'text-xl' : 'text-2xl'} font-bold text-slate-800`}>{title}</h2>
                    <button type="button" onClick={onClose} className={`${compact ? 'p-2' : 'p-3'} rounded-full border border-slate-300 text-slate-600 hover:bg-slate-50`} title="Cerrar">
                        <X className={`${compact ? 'h-4 w-4' : 'h-5 w-5'}`} />
                    </button>
                </div>
                <div className={`overflow-y-auto ${compact ? 'px-6 py-4' : 'px-8 py-6'}`}>{children}</div>
                {footer && <div className={`flex justify-end gap-3 border-t border-slate-200 bg-slate-50 ${compact ? 'px-6 py-3' : 'px-8 py-5'}`}>{footer}</div>}
            </div>
        </div>
    );
}

function TrabajadorForm({ trabajador, onClose }) {
    const [form, setForm] = useState(trabajador ?? {
        dni: '',
        nombres: '',
        apellidos: '',
        cargo: '',
        area: '',
        telefono: '',
        email: '',
        estado: 'activo',
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (trabajador?.id) {
            router.put(`/administracion/trabajadores/${trabajador.id}`, form, options);
        } else {
            router.post('/administracion/trabajadores', form, options);
        }
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title={trabajador?.id ? 'Editar trabajador' : 'Nuevo trabajador'}
                onClose={onClose}
                maxWidth="max-w-4xl"
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">{trabajador?.id ? 'Actualizar' : 'Guardar'}</button>
                    </>
                }
            >
                <div className="grid gap-4 md:grid-cols-3">
                    <Input label="DNI *" value={form.dni} onChange={(value) => setForm({ ...form, dni: value })} />
                    <Input label="Nombres *" value={form.nombres ?? ''} onChange={(value) => setForm({ ...form, nombres: value, nombre: `${value} ${form.apellidos ?? ''}`.trim() })} />
                    <Input label="Apellidos" value={form.apellidos ?? ''} onChange={(value) => setForm({ ...form, apellidos: value, nombre: `${form.nombres ?? ''} ${value}`.trim() })} />
                    <Input label="Cargo" value={form.cargo ?? ''} onChange={(value) => setForm({ ...form, cargo: value })} />
                    <Input label="Area" value={form.area ?? ''} onChange={(value) => setForm({ ...form, area: value })} />
                    <Input label="Telefono" value={form.telefono ?? ''} onChange={(value) => setForm({ ...form, telefono: value })} />
                    <Input label="Email" value={form.email ?? ''} onChange={(value) => setForm({ ...form, email: value })} />
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Estado</span>
                        <select value={form.estado ?? 'activo'} onChange={(event) => setForm({ ...form, estado: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </label>
                </div>
            </Modal>
        </form>
    );
}

function Input({ label, value, onChange }) {
    return (
        <label className="space-y-2">
            <span className="font-semibold text-slate-700">{label}</span>
            <input value={value ?? ''} onChange={(event) => onChange(event.target.value)} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
        </label>
    );
}

function SearchSelect({ label, options, value, onChange, placeholder = 'Buscar...', compact = false }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = options.find((option) => Number(option.id) === Number(value));
    const visibleValue = open ? query : (selected?.nombre ?? query);
    const filtered = options
        .filter((option) => {
            const text = `${option.nombre ?? ''} ${option.codigo ?? ''} ${option.familia?.nombre ?? ''}`.toLowerCase();
            return text.includes(query.toLowerCase());
        })
        .slice(0, 5);

    return (
        <label className="relative space-y-2">
            <span className="font-semibold text-slate-700">{label}</span>
            <input
                value={visibleValue ?? ''}
                onFocus={() => {
                    setOpen(true);
                    setQuery('');
                }}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                    onChange('');
                }}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
                placeholder={placeholder}
                className={`w-full rounded-lg border border-slate-300 px-4 pr-10 ${compact ? 'py-2.5' : 'py-3'}`}
            />
            <ChevronDown className={`pointer-events-none absolute right-4 h-5 w-5 text-slate-500 ${compact ? 'top-10' : 'top-11'}`} />
            {open && (
                <div className="absolute z-30 mt-1 max-h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    {filtered.length === 0 && <div className="px-4 py-3 text-slate-400">Sin resultados</div>}
                    {filtered.map((option) => (
                        <button
                            key={option.id}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onChange(option.id);
                                setQuery(option.nombre ?? '');
                                setOpen(false);
                            }}
                            className="block w-full px-4 py-3 text-left text-slate-800 hover:bg-slate-50"
                        >
                            <div className="font-semibold">{option.nombre}</div>
                            <div className="text-sm text-slate-500">{option.familia?.nombre ?? 'EPP'}</div>
                        </button>
                    ))}
                </div>
            )}
        </label>
    );
}

function TrabajadorEppsModal({ trabajador, asignaciones, productos, almacenes, centros, onClose }) {
    const [showAssign, setShowAssign] = useState(false);
    const [returnOpen, setReturnOpen] = useState(null);
    const [renewOpen, setRenewOpen] = useState(null);
    const [detailOpen, setDetailOpen] = useState(null);
    const [form, setForm] = useState({
        trabajador_id: trabajador.id,
        producto_id: '',
        almacen_id: almacenes[0]?.id ?? '',
        centro_costo_id: centros[0]?.id ?? '',
        cantidad: 1,
        talla: '',
        fecha_entrega: today,
        observaciones: '',
    });

    const submit = (event) => {
        event.preventDefault();
        router.post('/operaciones/epps', form, {
            preserveScroll: true,
            onSuccess: () => {
                setShowAssign(false);
                setForm((current) => ({ ...current, producto_id: '', cantidad: 1, talla: '', observaciones: '' }));
            },
        });
    };

    return (
        <>
            <Modal
                title={`EPPs - ${nombreTrabajador(trabajador)}`}
                onClose={onClose}
                maxWidth="max-w-[92rem]"
                compact
                footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cerrar</button>}
            >
                <div className="mb-4 grid gap-3 rounded-lg bg-slate-50 px-4 py-3 md:grid-cols-4">
                    <div><div className="text-sm text-slate-500">Trabajador</div><div className="font-bold">{nombreTrabajador(trabajador)}</div></div>
                    <div><div className="text-sm text-slate-500">DNI</div><div className="font-bold">{trabajador.dni ?? '-'}</div></div>
                    <div><div className="text-sm text-slate-500">Cargo</div><div className="font-bold">{trabajador.cargo ?? '-'}</div></div>
                    <div><div className="text-sm text-slate-500">Estado</div><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(trabajador.estado)}`}>{trabajador.estado}</span></div>
                </div>

                <div className="mb-4 flex justify-end">
                    <button type="button" onClick={() => setShowAssign((value) => !value)} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 font-semibold text-white">
                        <Plus className="h-5 w-5" /> Asignar EPP
                    </button>
                </div>

                {showAssign && (
                    <form onSubmit={submit} className="mb-4 rounded-lg border border-slate-800 p-4">
                        <div className="grid gap-3 md:grid-cols-3">
                            <SearchSelect
                                label="Producto EPP *"
                                options={productos}
                                value={form.producto_id}
                                onChange={(value) => setForm({ ...form, producto_id: value })}
                                placeholder="Buscar producto EPP..."
                                compact
                            />
                            <label className="space-y-2">
                                <span className="font-semibold text-slate-700">Cantidad *</span>
                                <input value={form.cantidad ?? ''} onChange={(event) => setForm({ ...form, cantidad: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-2.5" />
                            </label>
                            <label className="space-y-2">
                                <span className="font-semibold text-slate-700">Talla</span>
                                <input value={form.talla ?? ''} onChange={(event) => setForm({ ...form, talla: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-2.5" />
                            </label>
                            <label className="space-y-2">
                                <span className="font-semibold text-slate-700">Almacen *</span>
                                <select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-2.5">
                                    <option value="">Seleccionar</option>
                                    {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="font-semibold text-slate-700">Obra / Unidad</span>
                                <select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-2.5">
                                    <option value="">Sin centro</option>
                                    {centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.nombre}</option>)}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="font-semibold text-slate-700">Fecha entrega *</span>
                                <input type="date" value={form.fecha_entrega} onChange={(event) => setForm({ ...form, fecha_entrega: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-2.5" />
                            </label>
                        </div>
                        <div className="mt-3 flex justify-end">
                            <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-5 py-2.5 font-semibold text-white">
                                <Send className="h-5 w-5" /> Registrar entrega
                            </button>
                        </div>
                    </form>
                )}

                <div className="overflow-hidden rounded-lg border border-slate-200">
                    <table className="w-full table-fixed text-left">
                        <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                            <tr>
                                <th className="w-[26%] px-4 py-4">EPP / Producto</th>
                                <th className="w-[8%] px-4 py-4">Talla</th>
                                <th className="w-[8%] px-4 py-4">Cant.</th>
                                <th className="w-[12%] px-4 py-4">Entrega</th>
                                <th className="w-[12%] px-4 py-4">Vencimiento</th>
                                <th className="w-[12%] px-4 py-4">Estado</th>
                                <th className="w-[22%] px-4 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {asignaciones.map((asignacion) => {
                                const estado = estadoEpp(asignacion);
                                const active = !['devuelto', 'renovado', 'baja'].includes(asignacion.estado);

                                return (
                                    <tr key={asignacion.id} className="border-t border-slate-200">
                                        <td className="px-4 py-4 font-bold">{asignacion.producto?.nombre ?? '-'}</td>
                                        <td className="px-4 py-4">{asignacion.talla ?? '-'}</td>
                                        <td className="px-4 py-4">{asignacion.cantidad ?? 1}</td>
                                        <td className="px-4 py-4">{formatDate(asignacion.fecha_entrega)}</td>
                                        <td className="px-4 py-4">{formatDate(asignacion.fecha_vencimiento)}</td>
                                        <td className="px-4 py-4"><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(estado)}`}>{estado}</span></td>
                                        <td className="px-4 py-4">
                                            <div className="flex justify-end gap-2">
                                                <IconButton title="Ver detalle" onClick={() => setDetailOpen(asignacion)}><History className="h-5 w-5 text-sky-600" /></IconButton>
                                                {active && (
                                                    <>
                                                        <IconButton title="Renovar" onClick={() => setRenewOpen(asignacion)}>
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
                                    <td colSpan="7" className="px-5 py-10 text-center text-slate-400">Este trabajador no tiene EPPs asignados</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Modal>

            {detailOpen && <EppDetailModal asignacion={detailOpen} onClose={() => setDetailOpen(null)} />}
            {returnOpen && <ReturnEppModal asignacion={returnOpen} onClose={() => setReturnOpen(null)} />}
            {renewOpen && (
                <RenewEppModal
                    asignacion={renewOpen}
                    productos={productos}
                    almacenes={almacenes}
                    centros={centros}
                    onClose={() => setRenewOpen(null)}
                />
            )}
        </>
    );
}

function RenewEppModal({ asignacion, productos, almacenes, centros, onClose }) {
    const [form, setForm] = useState({
        producto_id: asignacion.producto_id ?? asignacion.producto?.id ?? '',
        almacen_id: asignacion.almacen?.id ?? asignacion.almacen_id ?? almacenes[0]?.id ?? '',
        centro_costo_id: asignacion.centro_costo?.id ?? asignacion.centro_costo_id ?? '',
        cantidad: asignacion.cantidad ?? 1,
        talla: asignacion.talla ?? '',
        fecha_entrega: today,
        motivo_devolucion: 'Renovacion de EPP',
        observaciones: '',
    });

    const submit = (event) => {
        event.preventDefault();
        router.put(`/operaciones/epps/${asignacion.id}/renovar`, form, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title="Renovar EPP"
                onClose={onClose}
                maxWidth="max-w-4xl"
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <RefreshCcw className="h-5 w-5" /> Confirmar renovacion
                        </button>
                    </>
                }
            >
                <div className="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Se cerrara el EPP actual como renovado y se registrara una nueva entrega con descuento de stock.
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <SearchSelect
                        label="Producto EPP *"
                        options={productos}
                        value={form.producto_id}
                        onChange={(value) => setForm({ ...form, producto_id: value })}
                        placeholder="Buscar producto EPP..."
                    />
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Cantidad *</span>
                        <input type="number" min="0.01" step="0.01" value={form.cantidad ?? ''} onChange={(event) => setForm({ ...form, cantidad: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
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
                        <span className="font-semibold text-slate-700">Fecha entrega *</span>
                        <input type="date" value={form.fecha_entrega} onChange={(event) => setForm({ ...form, fecha_entrega: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                </div>

                <div className="mt-4 grid gap-4 md:grid-cols-2">
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Motivo de renovacion</span>
                        <textarea value={form.motivo_devolucion ?? ''} onChange={(event) => setForm({ ...form, motivo_devolucion: event.target.value })} className="h-24 w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                    <label className="space-y-2">
                        <span className="font-semibold text-slate-700">Observaciones nueva entrega</span>
                        <textarea value={form.observaciones ?? ''} onChange={(event) => setForm({ ...form, observaciones: event.target.value })} className="h-24 w-full rounded-lg border border-slate-300 px-4 py-3" />
                    </label>
                </div>
            </Modal>
        </form>
    );
}

function EppDetailModal({ asignacion, onClose }) {
    const estado = estadoEpp(asignacion);

    return (
        <Modal
            title="Detalle de EPP"
            onClose={onClose}
            maxWidth="max-w-3xl"
            footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-6 py-3 font-semibold text-slate-700">Cerrar</button>}
        >
            <div className="grid gap-4 rounded-lg bg-slate-50 p-5 md:grid-cols-2">
                <div><div className="text-sm text-slate-500">Producto</div><div className="font-bold">{asignacion.producto?.nombre ?? '-'}</div></div>
                <div><div className="text-sm text-slate-500">Estado</div><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(estado)}`}>{estado}</span></div>
                <div><div className="text-sm text-slate-500">Cantidad</div><div className="font-bold">{asignacion.cantidad ?? 1}</div></div>
                <div><div className="text-sm text-slate-500">Talla</div><div className="font-bold">{asignacion.talla ?? '-'}</div></div>
                <div><div className="text-sm text-slate-500">Entrega</div><div className="font-bold">{formatDate(asignacion.fecha_entrega)}</div></div>
                <div><div className="text-sm text-slate-500">Vencimiento</div><div className="font-bold">{formatDate(asignacion.fecha_vencimiento)}</div></div>
                <div><div className="text-sm text-slate-500">Almacen</div><div className="font-bold">{asignacion.almacen?.nombre ?? '-'}</div></div>
                <div><div className="text-sm text-slate-500">Obra / Unidad</div><div className="font-bold">{asignacion.centro_costo?.nombre ?? '-'}</div></div>
            </div>
            <div className="mt-5 rounded-lg border border-slate-200 p-5">
                <div className="text-sm font-semibold uppercase text-slate-500">Observaciones</div>
                <div className="mt-2 text-slate-700">{asignacion.observaciones ?? '-'}</div>
            </div>
        </Modal>
    );
}

function ReturnEppModal({ asignacion, onClose }) {
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
                    <div className="font-bold">{asignacion.producto?.nombre}</div>
                    <div className="text-slate-500">Cantidad: {asignacion.cantidad ?? 1}</div>
                </div>
                <label className="mt-5 block space-y-2">
                    <span className="font-semibold text-slate-700">Motivo</span>
                    <textarea value={motivo} onChange={(event) => setMotivo(event.target.value)} className="h-24 w-full rounded-lg border border-slate-300 px-4 py-3" />
                </label>
            </Modal>
        </form>
    );
}

export default function Index({ trabajadores = [], eppAsignaciones = [], productosEpp = [], almacenes = [], centros = [] }) {
    const [formOpen, setFormOpen] = useState(null);
    const [eppsOpen, setEppsOpen] = useState(null);
    const [search, setSearch] = useState('');
    const eppsTrabajador = useMemo(() => {
        if (!eppsOpen?.id) return [];
        return eppAsignaciones.filter((row) => Number(row.trabajador_id ?? row.trabajador?.id) === Number(eppsOpen.id));
    }, [eppsOpen, eppAsignaciones]);
    const filteredTrabajadores = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return trabajadores;
        }

        return trabajadores.filter((trabajador) => [
            trabajador.dni,
            nombreTrabajador(trabajador),
            trabajador.nombres,
            trabajador.apellidos,
            trabajador.cargo,
            trabajador.area,
            trabajador.telefono,
            trabajador.email,
            trabajador.estado,
            trabajador.activo ? 'activo' : 'inactivo',
        ].filter(Boolean).join(' ').toLowerCase().includes(term));
    }, [trabajadores, search]);

    return (
        <AuthenticatedLayout headerTitle="Trabajadores" headerSubtitle="Logistica">
            <Head title="Trabajadores" />
            <div className="space-y-5">
                <FlashMessage />

                <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-5">
                            <div className="min-w-0">
                                <h2 className="text-base font-bold text-slate-950">Listado</h2>
                            </div>
                            <div className="relative w-full min-w-[260px] max-w-md">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Buscar trabajador..."
                                    className="h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-accent-400 focus:ring-2 focus:ring-accent-100"
                                />
                                {search && (
                                    <button type="button" onClick={() => setSearch('')} className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Limpiar busqueda">
                                        <X className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        </div>
                        <button onClick={() => setFormOpen({})} className="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <Plus className="h-4 w-4" /> Nuevo
                        </button>
                    </div>

                    <div className="p-5">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                                    <tr>
                                        <th className="w-[14%] px-5 py-4">DNI</th>
                                        <th className="w-[24%] px-5 py-4">Nombre</th>
                                        <th className="w-[20%] px-5 py-4">Cargo</th>
                                        <th className="w-[16%] px-5 py-4">Area</th>
                                        <th className="w-[10%] px-5 py-4">Estado</th>
                                        <th className="w-[16%] px-5 py-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredTrabajadores.map((trabajador) => (
                                        <tr key={trabajador.id} className="border-t border-slate-200">
                                            <td className="px-5 py-5 text-slate-900">{trabajador.dni}</td>
                                            <td className="px-5 py-5 text-slate-900">{nombreTrabajador(trabajador)}</td>
                                            <td className="px-5 py-5 text-slate-900">{trabajador.cargo ?? '-'}</td>
                                            <td className="px-5 py-5 text-slate-900">{trabajador.area ?? '-'}</td>
                                            <td className="px-5 py-5">
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${estadoClass(trabajador.estado)}`}>{trabajador.estado}</span>
                                            </td>
                                            <td className="px-5 py-5">
                                                <div className="flex justify-end gap-2">
                                                    <IconButton title="EPPs" onClick={() => setEppsOpen(trabajador)}><ShieldCheck className="h-5 w-5 text-emerald-600" /></IconButton>
                                                    <a title="Kardex EPP PDF" href={route('administracion.trabajadores.epp-kardex.pdf', trabajador.id)} target="_blank" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200 bg-white text-blue-600 transition hover:bg-blue-50">
                                                        <FileText className="h-5 w-5" />
                                                    </a>
                                                    <IconButton title="Editar" onClick={() => setFormOpen(trabajador)}><Edit2 className="h-5 w-5 text-slate-700" /></IconButton>
                                                    <IconButton title="Eliminar" onClick={() => router.delete(`/administracion/trabajadores/${trabajador.id}`, { preserveScroll: true })} className="border-red-200 text-red-600 hover:bg-red-50">
                                                        <Trash2 className="h-5 w-5" />
                                                    </IconButton>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredTrabajadores.length === 0 && (
                                        <tr>
                                            <td colSpan="6" className="px-5 py-10 text-center text-slate-400">
                                                {search ? 'No hay trabajadores que coincidan con la busqueda' : 'No hay trabajadores registrados'}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            {formOpen && <TrabajadorForm trabajador={formOpen.id ? formOpen : null} onClose={() => setFormOpen(null)} />}
            {eppsOpen && (
                <TrabajadorEppsModal
                    trabajador={eppsOpen}
                    asignaciones={eppsTrabajador}
                    productos={productosEpp}
                    almacenes={almacenes}
                    centros={centros}
                    onClose={() => setEppsOpen(null)}
                />
            )}
        </AuthenticatedLayout>
    );
}

