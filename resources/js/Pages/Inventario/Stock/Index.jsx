import { AdminCard } from '@/Components/Admin/Card';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

function number(value) {
    return Number(value || 0).toFixed(2);
}

function money(value) {
    return `S/ ${Number(value || 0).toFixed(2)}`;
}

export default function Index({ stocks }) {
    const rows = Array.isArray(stocks) ? stocks : (stocks?.data ?? []);

    const columns = [
        { key: 'almacen', label: 'Almacen', render: (row) => row.almacen?.nombre ?? '-' },
        { key: 'codigo', label: 'Codigo', render: (row) => row.producto?.codigo ?? '-' },
        { key: 'producto', label: 'Producto', render: (row) => row.producto?.nombre ?? '-' },
        { key: 'existencia', label: 'Existencia', render: (row) => number(row.stock_actual) },
        { key: 'minimo', label: 'Minimo', render: (row) => number(row.stock_minimo ?? row.producto?.stock_minimo) },
        { key: 'costo_promedio', label: 'Costo Prom.', render: (row) => number(row.costo_promedio) },
        {
            key: 'valor',
            label: 'Valor',
            render: (row) => money(Number(row.stock_actual || 0) * Number(row.costo_promedio || 0)),
        },
    ];

    return (
        <AuthenticatedLayout title="Inventario">
            <Head title="Inventario" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminCard
                    title="Inventario actual"
                    description="Existencias disponibles, costo promedio y valor por producto y almacen."
                >
                    <ResourceTable columns={columns} rows={rows} empty="Sin existencias registradas" />
                </AdminCard>
            </div>
        </AuthenticatedLayout>
    );
}
