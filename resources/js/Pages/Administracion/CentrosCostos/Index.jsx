import { ActionButton, AdminCard, SelectField, StatusBadge, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const emptyForm = {
    codigo: '',
    nombre: '',
    tipo: 'obra',
    responsable_id: '',
    ubicacion: '',
    activo: '1',
};

export default function Index({ centros, usuarios }) {
    const [editing, setEditing] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmTarget, setConfirmTarget] = useState(null);
    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm(emptyForm);

    const columns = useMemo(() => [
        { key: 'codigo', label: 'Codigo' },
        { key: 'nombre', label: 'Nombre' },
        { key: 'tipo', label: 'Tipo' },
        { key: 'responsable', label: 'Responsable', render: (row) => row.responsable?.name ?? '-' },
        { key: 'ubicacion', label: 'Ubicacion', render: (row) => row.ubicacion ?? '-' },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ], []);

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
            codigo: row.codigo ?? '',
            nombre: row.nombre ?? '',
            tipo: row.tipo ?? 'obra',
            responsable_id: row.responsable_id ?? '',
            ubicacion: row.ubicacion ?? '',
            activo: row.activo ? '1' : '0',
        });
        setModalOpen(true);
    }

    function submit(e) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeModal };
        editing
            ? put(route('administracion.centros-costos.update', editing.id), options)
            : post(route('administracion.centros-costos.store'), options);
    }
    function confirmDestroy() {
        if (!confirmTarget) return;
        destroy(route('administracion.centros-costos.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Centros de Costo">
            <Head title="Centros de Costo" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminModal show={modalOpen} onClose={closeModal} title={editing ? 'Editar centro de costo' : 'Nuevo centro de costo'} description="Obras, unidades operativas o areas internas." formId="centro-form" submitLabel={editing ? 'Actualizar' : 'Crear'} processing={processing}>
                    <form id="centro-form" onSubmit={submit} className="grid gap-4 md:grid-cols-3">
                        <TextField label="Codigo" value={data.codigo} onChange={(e) => setData('codigo', e.target.value)} error={errors.codigo} />
                        <TextField label="Nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} error={errors.nombre} />
                        <SelectField label="Tipo" value={data.tipo} onChange={(e) => setData('tipo', e.target.value)} error={errors.tipo}>
                            <option value="obra">Obra</option>
                            <option value="unidad">Unidad</option>
                            <option value="area">Area</option>
                        </SelectField>
                        <SelectField label="Responsable" value={data.responsable_id} onChange={(e) => setData('responsable_id', e.target.value)} error={errors.responsable_id}>
                            <option value="">Sin responsable</option>
                            {usuarios.map((usuario) => <option key={usuario.id} value={usuario.id}>{usuario.name}</option>)}
                        </SelectField>
                        <TextField label="Ubicacion" value={data.ubicacion} onChange={(e) => setData('ubicacion', e.target.value)} error={errors.ubicacion} />
                        <SelectField label="Estado" value={data.activo} onChange={(e) => setData('activo', e.target.value)} error={errors.activo}>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </SelectField>
                    </form>
                </AdminModal>

                <AdminCard title="Listado" actions={<ActionButton type="button" tone="secondary" onClick={startCreate}><Plus className="mr-2 h-4 w-4" /> Nuevo</ActionButton>}>
                    <ResourceTable
                        columns={columns}
                        rows={centros}
                        renderActions={(row) => (
                            <div className="inline-flex gap-2">
                                <ActionButton type="button" tone="secondary" onClick={() => startEdit(row)}><Pencil className="h-4 w-4" /></ActionButton>
                                <ActionButton type="button" tone="danger" onClick={() => setConfirmTarget(row)}><Trash2 className="h-4 w-4" /></ActionButton>
                            </div>
                        )}
                    />
                </AdminCard>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Desactivar centro de costo"
                    message={confirmTarget ? `Se desactivara el centro de costo ${confirmTarget.nombre}.` : ''}
                    confirmText="Desactivar"
                    tone="danger"
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
