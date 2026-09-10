import { ActionButton, AdminCard, SelectField, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';

export default function Edit({ empresa }) {
    const [modalOpen, setModalOpen] = useState(false);
    const { data, setData, put, processing, errors } = useForm({
        razon_social: empresa?.razon_social ?? '',
        nombre_comercial: empresa?.nombre_comercial ?? '',
        ruc: empresa?.ruc ?? '',
        direccion: empresa?.direccion ?? '',
        ciudad: empresa?.ciudad ?? 'Lima',
        pais: empresa?.pais ?? 'Peru',
        telefono: empresa?.telefono ?? '',
        email: empresa?.email ?? '',
        rubro: empresa?.rubro ?? 'Servicios logisticos y contratistas para mineria',
        moneda: empresa?.moneda ?? 'PEN',
        metodo_valorizacion: empresa?.metodo_valorizacion ?? 'promedio_ponderado',
        bloquear_stock_negativo: empresa?.bloquear_stock_negativo ? '1' : '0',
    });

    function submit(e) {
        e.preventDefault();
        put(route('administracion.empresa.update'), {
            preserveScroll: true,
            onSuccess: () => setModalOpen(false),
        });
    }

    return (
        <AuthenticatedLayout title="Configuracion de Empresa">
            <Head title="Configuracion de Empresa" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminCard
                    title="Datos generales"
                    description="Informacion usada en documentos, encabezados y parametros operativos."
                    actions={<ActionButton type="button" tone="secondary" onClick={() => setModalOpen(true)}><Pencil className="mr-2 h-4 w-4" /> Editar</ActionButton>}
                >
                    <div className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                        <div><span className="font-semibold text-slate-500">Razon social:</span> {empresa?.razon_social ?? '-'}</div>
                        <div><span className="font-semibold text-slate-500">Nombre comercial:</span> {empresa?.nombre_comercial ?? '-'}</div>
                        <div><span className="font-semibold text-slate-500">RUC:</span> {empresa?.ruc ?? '-'}</div>
                        <div><span className="font-semibold text-slate-500">Ciudad:</span> {empresa?.ciudad ?? '-'}</div>
                        <div><span className="font-semibold text-slate-500">Pais:</span> {empresa?.pais ?? '-'}</div>
                        <div><span className="font-semibold text-slate-500">Moneda:</span> {empresa?.moneda ?? '-'}</div>
                    </div>
                </AdminCard>
                <AdminModal show={modalOpen} onClose={() => setModalOpen(false)} title="Editar empresa" description="Actualiza los datos generales y parametros operativos." formId="empresa-form" submitLabel="Guardar configuracion" processing={processing} maxWidth="2xl">
                    <form id="empresa-form" onSubmit={submit} className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <TextField label="Razon social" value={data.razon_social} onChange={(e) => setData('razon_social', e.target.value)} error={errors.razon_social} />
                        <TextField label="Nombre comercial" value={data.nombre_comercial} onChange={(e) => setData('nombre_comercial', e.target.value)} error={errors.nombre_comercial} />
                        <TextField label="RUC" value={data.ruc} onChange={(e) => setData('ruc', e.target.value)} error={errors.ruc} maxLength={11} />
                        <TextField label="Direccion" value={data.direccion} onChange={(e) => setData('direccion', e.target.value)} error={errors.direccion} />
                        <TextField label="Ciudad" value={data.ciudad} onChange={(e) => setData('ciudad', e.target.value)} error={errors.ciudad} />
                        <TextField label="Pais" value={data.pais} onChange={(e) => setData('pais', e.target.value)} error={errors.pais} />
                        <TextField label="Telefono" value={data.telefono} onChange={(e) => setData('telefono', e.target.value)} error={errors.telefono} />
                        <TextField label="Email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                        <TextField label="Rubro" value={data.rubro} onChange={(e) => setData('rubro', e.target.value)} error={errors.rubro} />
                        <SelectField label="Moneda" value={data.moneda} onChange={(e) => setData('moneda', e.target.value)} error={errors.moneda}>
                            <option value="PEN">PEN - Sol</option>
                            <option value="USD">USD - Dolar</option>
                        </SelectField>
                        <SelectField label="Valorizacion" value={data.metodo_valorizacion} onChange={(e) => setData('metodo_valorizacion', e.target.value)} error={errors.metodo_valorizacion}>
                            <option value="promedio_ponderado">Promedio ponderado</option>
                        </SelectField>
                        <SelectField label="Stock negativo" value={data.bloquear_stock_negativo} onChange={(e) => setData('bloquear_stock_negativo', e.target.value)} error={errors.bloquear_stock_negativo}>
                            <option value="1">Bloqueado</option>
                            <option value="0">Permitido</option>
                        </SelectField>
                    </form>
                </AdminModal>
            </div>
        </AuthenticatedLayout>
    );
}
