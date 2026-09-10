import { ActionButton, AdminCard, SelectField, StatusBadge, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

const emptyForm = { codigo: '', nombre: '', tipo: 'principal', ubicacion: '', centro_costo_id: '', responsable_id: '', activo: '1' };

export default function Index({ almacenes, centros, usuarios }) {
    const [editing, setEditing] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmTarget, setConfirmTarget] = useState(null);
    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm(emptyForm);
    const columns = useMemo(() => [
        { key: 'codigo', label: 'Codigo' },
        { key: 'nombre', label: 'Nombre' },
        { key: 'tipo', label: 'Tipo' },
        { key: 'centro', label: 'Centro', render: (row) => row.centro_costo?.nombre ?? '-' },
        { key: 'responsable', label: 'Responsable', render: (row) => row.responsable?.name ?? '-' },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ], []);

    function closeModal() { setModalOpen(false); setEditing(null); clearErrors(); reset(); }
    function startCreate() { setEditing(null); clearErrors(); reset(); setModalOpen(true); }
    function startEdit(row) {
        setEditing(row); clearErrors();
        setData({
            codigo: row.codigo ?? '', nombre: row.nombre ?? '', tipo: row.tipo ?? 'principal', ubicacion: row.ubicacion ?? '',
            centro_costo_id: row.centro_costo_id ?? '', responsable_id: row.responsable_id ?? '', activo: row.activo ? '1' : '0',
        });
        setModalOpen(true);
    }
    function submit(e) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeModal };
        editing ? put(route('administracion.almacenes.update', editing.id), options) : post(route('administracion.almacenes.store'), options);
    }
    function confirmDestroy() {
        if (!confirmTarget) return;
        destroy(route('administracion.almacenes.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Almacenes">
            <Head title="Almacenes" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminModal show={modalOpen} onClose={closeModal} title={editing ? 'Editar almacen' : 'Nuevo almacen'} description="Almacenes fisicos o logicos para control de stock." formId="almacen-form" submitLabel={editing ? 'Actualizar' : 'Crear'} processing={processing}>
                    <form id="almacen-form" onSubmit={submit} className="grid gap-4 md:grid-cols-3">
                        <TextField label="Codigo" value={data.codigo} onChange={(e) => setData('codigo', e.target.value)} error={errors.codigo} />
                        <TextField label="Nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} error={errors.nombre} />
                        <SelectField label="Tipo" value={data.tipo} onChange={(e) => setData('tipo', e.target.value)} error={errors.tipo}>
                            <option value="principal">Principal</option><option value="obra">Obra</option><option value="movil">Movil</option><option value="temporal">Temporal</option>
                        </SelectField>
                        <SelectField label="Centro de costo" value={data.centro_costo_id} onChange={(e) => setData('centro_costo_id', e.target.value)} error={errors.centro_costo_id}>
                            <option value="">Sin centro</option>{centros.map((centro) => <option key={centro.id} value={centro.id}>{centro.codigo} - {centro.nombre}</option>)}
                        </SelectField>
                        <SelectField label="Responsable" value={data.responsable_id} onChange={(e) => setData('responsable_id', e.target.value)} error={errors.responsable_id}>
                            <option value="">Sin responsable</option>{usuarios.map((usuario) => <option key={usuario.id} value={usuario.id}>{usuario.name}</option>)}
                        </SelectField>
                        <SelectField label="Estado" value={data.activo} onChange={(e) => setData('activo', e.target.value)} error={errors.activo}><option value="1">Activo</option><option value="0">Inactivo</option></SelectField>
                        <TextField label="Ubicacion" value={data.ubicacion} onChange={(e) => setData('ubicacion', e.target.value)} error={errors.ubicacion} />
                    </form>
                </AdminModal>
                <AdminCard title="Listado" actions={<ActionButton type="button" tone="secondary" onClick={startCreate}><Plus className="mr-2 h-4 w-4" /> Nuevo</ActionButton>}>
                    <ResourceTable columns={columns} rows={almacenes} renderActions={(row) => (
                        <div className="inline-flex gap-2"><ActionButton type="button" tone="secondary" onClick={() => startEdit(row)}><Pencil className="h-4 w-4" /></ActionButton><ActionButton type="button" tone="danger" onClick={() => setConfirmTarget(row)}><Trash2 className="h-4 w-4" /></ActionButton></div>
                    )} />
                </AdminCard>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Desactivar almacen"
                    message={confirmTarget ? `Se desactivara el almacen ${confirmTarget.nombre}.` : ''}
                    confirmText="Desactivar"
                    tone="danger"
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
