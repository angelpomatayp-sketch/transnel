import { ActionButton, AdminCard, SelectField, StatusBadge, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';

const emptyForm = {
    name: '',
    email: '',
    password: '',
    dni: '',
    telefono: '',
    centro_costo_id: '',
    almacen_id: '',
    activo: '1',
    role: 'almacenero',
};

export default function Index({ usuarios, roles, centros, almacenes }) {
    const [editing, setEditing] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmTarget, setConfirmTarget] = useState(null);
    const [search, setSearch] = useState('');
    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm(emptyForm);
    const columns = useMemo(() => [
        { key: 'name', label: 'Nombre' },
        { key: 'email', label: 'Email' },
        { key: 'role', label: 'Rol', render: (row) => row.roles?.[0]?.name ?? '-' },
        { key: 'centro', label: 'Centro', render: (row) => row.centro_costo?.nombre ?? '-' },
        { key: 'almacen', label: 'Almacen', render: (row) => row.almacen?.nombre ?? '-' },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ], []);
    const filteredUsuarios = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return usuarios;
        }

        return usuarios.filter((usuario) => [
            usuario.name,
            usuario.email,
            usuario.dni,
            usuario.telefono,
            usuario.roles?.[0]?.name,
            usuario.centro_costo?.nombre,
            usuario.centro_costo?.codigo,
            usuario.almacen?.nombre,
            usuario.almacen?.codigo,
            usuario.activo ? 'activo' : 'inactivo',
        ].filter(Boolean).join(' ').toLowerCase().includes(term));
    }, [usuarios, search]);

    function closeModal() {
        setModalOpen(false);
        setEditing(null);
        clearErrors();
        reset();
    }

    function startCreate() {
        setEditing(null);
        clearErrors();
        reset();
        setModalOpen(true);
    }

    function startEdit(row) {
        setEditing(row);
        clearErrors();
        setData({
            name: row.name ?? '',
            email: row.email ?? '',
            password: '',
            dni: row.dni ?? '',
            telefono: row.telefono ?? '',
            centro_costo_id: row.centro_costo_id ?? '',
            almacen_id: row.almacen_id ?? '',
            activo: row.activo ? '1' : '0',
            role: row.roles?.[0]?.name ?? 'almacenero',
        });
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeModal };
        editing
            ? put(route('administracion.usuarios.update', editing.id), options)
            : post(route('administracion.usuarios.store'), options);
    }
    function confirmDestroy() {
        if (!confirmTarget) return;
        destroy(route('administracion.usuarios.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Usuarios">
            <Head title="Usuarios" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminModal show={modalOpen} onClose={closeModal} title={editing ? 'Editar usuario' : 'Nuevo usuario'} description="Cuentas con acceso al sistema y rol operativo." formId="usuario-form" submitLabel={editing ? 'Actualizar' : 'Crear'} processing={processing}>
                    <form id="usuario-form" onSubmit={submit} className="grid gap-4 md:grid-cols-3">
                        <TextField label="Nombre" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                        <TextField label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                        <TextField label={editing ? 'Password nuevo' : 'Password'} type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                        <TextField label="DNI" value={data.dni} onChange={(e) => setData('dni', e.target.value)} error={errors.dni} />
                        <TextField label="Telefono" value={data.telefono} onChange={(e) => setData('telefono', e.target.value)} error={errors.telefono} />
                        <SelectField label="Rol" value={data.role} onChange={(e) => setData('role', e.target.value)} error={errors.role}>
                            {roles.map((role) => <option key={role.id} value={role.name}>{role.name}</option>)}
                        </SelectField>
                        <SelectField label="Centro de costo" value={data.centro_costo_id} onChange={(e) => setData('centro_costo_id', e.target.value)} error={errors.centro_costo_id}>
                            <option value="">Sin centro</option>{centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.codigo} - {centro.nombre}</option>)}
                        </SelectField>
                        <SelectField label="Almacen" value={data.almacen_id} onChange={(e) => setData('almacen_id', e.target.value)} error={errors.almacen_id}>
                            <option value="">Sin almacen</option>{almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.codigo} - {almacen.nombre}</option>)}
                        </SelectField>
                        <SelectField label="Estado" value={data.activo} onChange={(e) => setData('activo', e.target.value)} error={errors.activo}><option value="1">Activo</option><option value="0">Inactivo</option></SelectField>
                    </form>
                </AdminModal>
                <AdminCard
                    title="Listado"
                    filters={(
                        <div className="relative w-full min-w-[260px] max-w-md">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Buscar usuario..."
                                className="h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-accent-400 focus:ring-2 focus:ring-accent-100"
                            />
                            {search && (
                                <button type="button" onClick={() => setSearch('')} className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Limpiar busqueda">
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    )}
                    actions={<ActionButton type="button" tone="secondary" onClick={startCreate}><Plus className="mr-2 h-4 w-4" /> Nuevo</ActionButton>}
                >
                    <ResourceTable columns={columns} rows={filteredUsuarios} renderActions={(row) => (
                        <div className="inline-flex gap-2"><ActionButton type="button" tone="secondary" onClick={() => startEdit(row)}><Pencil className="h-4 w-4" /></ActionButton><ActionButton type="button" tone="danger" onClick={() => setConfirmTarget(row)}><Trash2 className="h-4 w-4" /></ActionButton></div>
                    )} />
                </AdminCard>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Desactivar usuario"
                    message={confirmTarget ? `Se desactivara el usuario ${confirmTarget.name}.` : ''}
                    confirmText="Desactivar"
                    tone="danger"
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
