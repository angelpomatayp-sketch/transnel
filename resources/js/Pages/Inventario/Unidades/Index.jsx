import { ActionButton, AdminCard, StatusBadge, TextField, SelectField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const emptyForm = { codigo: '', nombre: '', abreviatura: '', activo: '1' };

export default function Index({ unidades }) {
    const [editing, setEditing] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmTarget, setConfirmTarget] = useState(null);
    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm(emptyForm);
    const columns = useMemo(() => [
        { key: 'codigo', label: 'Codigo' },
        { key: 'nombre', label: 'Nombre' },
        { key: 'abreviatura', label: 'Abreviatura' },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ], []);

    function closeModal() { setModalOpen(false); setEditing(null); clearErrors(); reset(); }
    function startCreate() { setEditing(null); clearErrors(); reset(); setModalOpen(true); }
    function startEdit(row) {
        setEditing(row); clearErrors();
        setData({ codigo: row.codigo ?? '', nombre: row.nombre ?? '', abreviatura: row.abreviatura ?? '', activo: row.activo ? '1' : '0' });
        setModalOpen(true);
    }
    function submit(e) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeModal };
        editing ? put(route('inventario.unidades.update', editing.id), options) : post(route('inventario.unidades.store'), options);
    }
    function confirmDestroy() {
        if (!confirmTarget) return;
        destroy(route('inventario.unidades.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Unidades">
            <Head title="Unidades" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminModal show={modalOpen} onClose={closeModal} title={editing ? 'Editar unidad' : 'Nueva unidad'} description="Unidades de medida usadas por productos." formId="unidad-form" submitLabel={editing ? 'Actualizar' : 'Crear'} processing={processing}>
                    <form id="unidad-form" onSubmit={submit} className="grid gap-4 md:grid-cols-4">
                        <TextField label="Codigo" value={data.codigo} onChange={(e) => setData('codigo', e.target.value)} error={errors.codigo} />
                        <TextField label="Nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} error={errors.nombre} />
                        <TextField label="Abreviatura" value={data.abreviatura} onChange={(e) => setData('abreviatura', e.target.value)} error={errors.abreviatura} />
                        <SelectField label="Estado" value={data.activo} onChange={(e) => setData('activo', e.target.value)} error={errors.activo}><option value="1">Activo</option><option value="0">Inactivo</option></SelectField>
                    </form>
                </AdminModal>
                <AdminCard title="Listado" actions={<ActionButton type="button" tone="secondary" onClick={startCreate}><Plus className="mr-2 h-4 w-4" /> Nueva</ActionButton>}>
                    <ResourceTable columns={columns} rows={unidades} renderActions={(row) => (
                        <div className="inline-flex gap-2"><ActionButton type="button" tone="secondary" onClick={() => startEdit(row)}><Pencil className="h-4 w-4" /></ActionButton><ActionButton type="button" tone="danger" onClick={() => setConfirmTarget(row)}><Trash2 className="h-4 w-4" /></ActionButton></div>
                    )} />
                </AdminCard>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Desactivar unidad"
                    message={confirmTarget ? `Se desactivara la unidad ${confirmTarget.nombre}.` : ''}
                    confirmText="Desactivar"
                    tone="danger"
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
