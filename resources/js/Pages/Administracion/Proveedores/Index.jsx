import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Edit2, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

function Modal({ title, children, onClose, footer }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
            <div className="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-7 py-4">
                    <h2 className="text-xl font-bold text-slate-800">{title}</h2>
                    <button type="button" onClick={onClose} className="rounded-full border border-slate-300 p-2 text-slate-600 hover:bg-slate-50"><X className="h-5 w-5" /></button>
                </div>
                <div className="overflow-y-auto px-7 py-5">{children}</div>
                {footer && <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-7 py-4">{footer}</div>}
            </div>
        </div>
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

function ProveedorForm({ proveedor, onClose }) {
    const [form, setForm] = useState(proveedor ?? {
        ruc: '',
        razon_social: '',
        nombre_comercial: '',
        contacto: '',
        telefono: '',
        email: '',
        direccion: '',
        activo: true,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        proveedor?.id
            ? router.put(`/administracion/proveedores/${proveedor.id}`, form, options)
            : router.post('/administracion/proveedores', form, options);
    };

    return (
        <form onSubmit={submit}>
            <Modal
                title={proveedor?.id ? 'Editar proveedor' : 'Nuevo proveedor'}
                onClose={onClose}
                footer={
                    <>
                        <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" className="rounded-lg bg-slate-800 px-5 py-3 font-semibold text-white">{proveedor?.id ? 'Actualizar' : 'Guardar'}</button>
                    </>
                }
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="RUC" value={form.ruc} onChange={(value) => setForm({ ...form, ruc: value })} />
                    <Input label="Razon social *" value={form.razon_social} onChange={(value) => setForm({ ...form, razon_social: value })} />
                    <Input label="Nombre comercial" value={form.nombre_comercial} onChange={(value) => setForm({ ...form, nombre_comercial: value })} />
                    <Input label="Contacto" value={form.contacto} onChange={(value) => setForm({ ...form, contacto: value })} />
                    <Input label="Telefono" value={form.telefono} onChange={(value) => setForm({ ...form, telefono: value })} />
                    <Input label="Email" value={form.email} onChange={(value) => setForm({ ...form, email: value })} />
                    <div className="md:col-span-2"><Input label="Direccion" value={form.direccion} onChange={(value) => setForm({ ...form, direccion: value })} /></div>
                    <label className="flex items-center gap-3 font-semibold text-slate-700">
                        <input type="checkbox" checked={!!form.activo} onChange={(event) => setForm({ ...form, activo: event.target.checked })} className="h-5 w-5 rounded border-slate-300" />
                        Activo
                    </label>
                </div>
            </Modal>
        </form>
    );
}

export default function Index({ proveedores = [] }) {
    const [formOpen, setFormOpen] = useState(null);
    return (
        <AuthenticatedLayout headerTitle="Proveedores" headerSubtitle="Logistica">
            <Head title="Proveedores" />
            <div className="px-6 py-8 xl:px-10">
                <FlashMessage />
                <section className="mx-auto w-full max-w-[92rem] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-8 py-7">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Proveedores</h1>
                            <p className="mt-2 text-slate-500">Maestro de proveedores para ordenes de compra.</p>
                        </div>
                        <button onClick={() => setFormOpen({})} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white">
                            <Plus className="h-5 w-5" /> Nuevo
                        </button>
                    </div>
                    <div className="p-8">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600">
                                    <tr>
                                        <th className="px-5 py-4">RUC</th>
                                        <th className="px-5 py-4">Razon social</th>
                                        <th className="px-5 py-4">Contacto</th>
                                        <th className="px-5 py-4">Telefono</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {proveedores.map((proveedor) => (
                                        <tr key={proveedor.id} className="border-t border-slate-200">
                                            <td className="px-5 py-5">{proveedor.ruc ?? '-'}</td>
                                            <td className="px-5 py-5 font-semibold">{proveedor.razon_social}</td>
                                            <td className="px-5 py-5">{proveedor.contacto ?? '-'}</td>
                                            <td className="px-5 py-5">{proveedor.telefono ?? '-'}</td>
                                            <td className="px-5 py-5"><span className={`rounded-full px-3 py-1 text-xs font-bold ${proveedor.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{proveedor.activo ? 'Activo' : 'Inactivo'}</span></td>
                                            <td className="px-5 py-5">
                                                <div className="flex justify-end gap-2">
                                                    <button title="Editar" onClick={() => setFormOpen(proveedor)} className="rounded-lg border border-slate-200 p-3"><Edit2 className="h-5 w-5" /></button>
                                                    <button title="Eliminar" onClick={() => router.delete(`/administracion/proveedores/${proveedor.id}`, { preserveScroll: true })} className="rounded-lg border border-red-200 p-3 text-red-600"><Trash2 className="h-5 w-5" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {proveedores.length === 0 && <tr><td colSpan="6" className="px-5 py-10 text-center text-slate-400">No hay proveedores registrados</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
            {formOpen && <ProveedorForm proveedor={formOpen.id ? formOpen : null} onClose={() => setFormOpen(null)} />}
        </AuthenticatedLayout>
    );
}

