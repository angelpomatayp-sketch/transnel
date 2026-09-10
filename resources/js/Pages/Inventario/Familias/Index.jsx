import { ActionButton, AdminCard, StatusBadge, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';

const initialForm = {
    codigo: '',
    nombre: '',
    descripcion: '',
    activo: true,
    es_epp: false,
    categoria_epp: '',
};

export default function Index({ familias, categoriasEpp = [] }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirmTarget, setConfirmTarget] = useState(null);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(initialForm);

    function openCreate() {
        setEditing(null);
        clearErrors();
        reset();
        setData(initialForm);
        setModalOpen(true);
    }

    function openEdit(familia) {
        setEditing(familia);
        clearErrors();
        setData({
            codigo: familia.codigo ?? '',
            nombre: familia.nombre ?? '',
            descripcion: familia.descripcion ?? '',
            activo: Boolean(familia.activo),
            es_epp: Boolean(familia.es_epp),
            categoria_epp: familia.categoria_epp ?? '',
        });
        setModalOpen(true);
    }

    function submit(event) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setEditing(null);
                setModalOpen(false);
            },
        };

        if (editing) {
            put(route('inventario.familias.update', editing.id), options);
            return;
        }

        post(route('inventario.familias.store'), options);
    }

    function destroy(familia) {
        if (familia.productos_count > 0) {
            return;
        }

        setConfirmTarget(familia);
    }

    function confirmDestroy() {
        if (!confirmTarget) {
            return;
        }

        router.delete(route('inventario.familias.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    const columns = [
        { key: 'codigo', label: 'Codigo' },
        { key: 'nombre', label: 'Nombre' },
        {
            key: 'es_epp',
            label: 'EPP',
            render: (row) => row.es_epp ? (
                <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                    <ShieldCheck className="mr-1 h-3.5 w-3.5" />
                    Si
                </span>
            ) : 'No',
        },
        { key: 'categoria_epp', label: 'Categoria EPP', render: (row) => row.categoria_epp ?? '-' },
        { key: 'productos_count', label: 'Productos', render: (row) => row.productos_count ?? 0 },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ];

    return (
        <AuthenticatedLayout title="Familias">
            <Head title="Familias" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminCard
                    title="Familias"
                    description="Clasificacion del catalogo y reglas para productos EPP."
                    actions={
                        <ActionButton type="button" onClick={openCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo
                        </ActionButton>
                    }
                >
                    <ResourceTable
                        columns={columns}
                        rows={familias}
                        renderActions={(row) => (
                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => openEdit(row)}
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50"
                                    title="Editar"
                                    aria-label="Editar"
                                >
                                    <Pencil className="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(row)}
                                    disabled={row.productos_count > 0}
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-300"
                                    title={row.productos_count > 0 ? 'Tiene productos asociados' : 'Eliminar'}
                                    aria-label="Eliminar"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </div>
                        )}
                    />
                </AdminCard>

                <AdminModal
                    show={modalOpen}
                    onClose={() => setModalOpen(false)}
                    title={editing ? 'Editar familia' : 'Nueva familia'}
                    description="Una familia EPP activa campos y validaciones especiales en productos."
                    formId="familia-form"
                    submitLabel={editing ? 'Actualizar' : 'Crear'}
                    processing={processing}
                    maxWidth="2xl"
                >
                    <form id="familia-form" onSubmit={submit} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <TextField label="Codigo" value={data.codigo} onChange={(event) => setData('codigo', event.target.value.toUpperCase())} error={errors.codigo} placeholder="Ej: MAN" />
                            <TextField label="Nombre" value={data.nombre} onChange={(event) => setData('nombre', event.target.value)} error={errors.nombre} placeholder="Ej: Proteccion de manos" />
                        </div>
                        <label className="block">
                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripcion</span>
                            <textarea
                                value={data.descripcion}
                                onChange={(event) => setData('descripcion', event.target.value)}
                                rows={3}
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                            />
                            {errors.descripcion && <p className="mt-1 text-xs font-medium text-red-600">{errors.descripcion}</p>}
                        </label>
                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2">
                                <input type="checkbox" checked={data.activo} onChange={(event) => setData('activo', event.target.checked)} className="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-accent-400" />
                                <span className="text-sm font-semibold text-slate-700">Familia activa</span>
                            </label>
                            <label className="flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">
                                <input
                                    type="checkbox"
                                    checked={data.es_epp}
                                    onChange={(event) => {
                                        setData({
                                            ...data,
                                            es_epp: event.target.checked,
                                            categoria_epp: event.target.checked ? data.categoria_epp : '',
                                        });
                                    }}
                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-300"
                                />
                                <span className="text-sm font-semibold text-emerald-800">Esta familia es EPP</span>
                            </label>
                        </div>
                        {data.es_epp && (
                            <label className="block">
                                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Categoria EPP</span>
                                <select value={data.categoria_epp} onChange={(event) => setData('categoria_epp', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100">
                                    <option value="">Seleccionar</option>
                                    {categoriasEpp.map((categoria) => <option key={categoria} value={categoria}>{categoria}</option>)}
                                </select>
                                {errors.categoria_epp && <p className="mt-1 text-xs font-medium text-red-600">{errors.categoria_epp}</p>}
                            </label>
                        )}
                    </form>
                </AdminModal>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Eliminar familia"
                    message={confirmTarget ? `Se eliminara la familia ${confirmTarget.nombre}.` : ''}
                    confirmText="Eliminar"
                    tone="danger"
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}


