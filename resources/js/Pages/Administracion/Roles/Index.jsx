import { ActionButton, AdminCard } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';

export default function Index({ roles, permissions }) {
    const [selected, setSelected] = useState(roles[0] ?? null);
    const [modalOpen, setModalOpen] = useState(false);
    const { data, setData, put, processing } = useForm({
        permissions: selected?.permissions?.map((permission) => permission.name) ?? [],
    });

    function choose(role) {
        setSelected(role);
        setData('permissions', role.permissions.map((permission) => permission.name));
        setModalOpen(true);
    }

    function toggle(permission) {
        setData(
            'permissions',
            data.permissions.includes(permission)
                ? data.permissions.filter((item) => item !== permission)
                : [...data.permissions, permission],
        );
    }

    function submit(e) {
        e.preventDefault();
        put(route('administracion.roles.update', selected.id), {
            preserveScroll: true,
            onSuccess: () => setModalOpen(false),
        });
    }

    return (
        <AuthenticatedLayout title="Roles y Permisos">
            <Head title="Roles y Permisos" />
            <div className="space-y-5">
                <FlashMessage />
                <div className="grid gap-5 lg:grid-cols-[280px_1fr]">
                    <AdminCard title="Roles">
                        <div className="space-y-2">
                            {roles.map((role) => (
                                <button
                                    key={role.id}
                                    onClick={() => choose(role)}
                                    className={`w-full rounded-md px-4 py-3 text-left text-sm font-semibold ${selected?.id === role.id ? 'bg-brand-700 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'}`}
                                >
                                    {role.name}
                                </button>
                            ))}
                        </div>
                    </AdminCard>
                    <AdminCard
                        title={`Permisos: ${selected?.name ?? ''}`}
                        description="Selecciona un rol para editar sus permisos en modal."
                        actions={selected && <ActionButton type="button" tone="secondary" onClick={() => setModalOpen(true)}><Pencil className="mr-2 h-4 w-4" /> Editar permisos</ActionButton>}
                    >
                        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                            {(selected?.permissions ?? []).map((permission) => (
                                <span key={permission.id} className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    {permission.name}
                                </span>
                            ))}
                        </div>
                    </AdminCard>
                </div>
                <AdminModal show={modalOpen} onClose={() => setModalOpen(false)} title={`Permisos: ${selected?.name ?? ''}`} description="Ajusta los permisos disponibles por rol operativo." formId="roles-form" submitLabel="Guardar permisos" processing={processing} maxWidth="2xl">
                    {selected && (
                        <form id="roles-form" onSubmit={submit} className="space-y-5">
                            <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                {permissions.map((permission) => (
                                    <label key={permission.id} className="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                        <input type="checkbox" checked={data.permissions.includes(permission.name)} onChange={() => toggle(permission.name)} className="rounded border-slate-300 text-accent-400 focus:ring-accent-400" />
                                        {permission.name}
                                    </label>
                                ))}
                            </div>
                        </form>
                    )}
                </AdminModal>
            </div>
        </AuthenticatedLayout>
    );
}


