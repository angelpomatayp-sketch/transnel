import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    ClipboardEdit,
    FileStack,
    Package,
    Repeat2,
    Shield,
    TrendingUp,
    Truck,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const toneClasses = {
    brand: 'bg-brand-100 text-brand-700',
    gold: 'bg-accent-100 text-accent-700',
    orange: 'bg-orange-100 text-orange-600',
    violet: 'bg-violet-100 text-violet-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    cyan: 'bg-cyan-100 text-cyan-600',
    slate: 'bg-slate-100 text-brand-500',
    red: 'bg-red-100 text-red-600',
};

function formatCurrency(value) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatNumber(value) {
    return new Intl.NumberFormat('es-PE', {
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function resolveKpis(values = {}) {
    return [
        { label: 'Valor Inventario', value: formatCurrency(values.valorInventario), icon: FileStack, tone: 'brand' },
        { label: 'Total Productos', value: formatNumber(values.totalProductos), icon: Package, tone: 'gold' },
        { label: 'Stock Bajo', value: formatNumber(values.stockBajo), icon: AlertTriangle, tone: 'orange' },
        { label: 'Movimientos del Mes', value: formatNumber(values.movimientosMes), icon: Repeat2, tone: 'violet' },
        { label: 'Requerimientos Pendientes', value: formatNumber(values.requerimientosPendientes), icon: ClipboardEdit, tone: 'yellow' },
        { label: 'Ordenes por Recibir', value: formatNumber(values.ordenesPorRecibir), icon: Truck, tone: 'cyan' },
        { label: 'Consumo del Mes', value: formatCurrency(values.consumoMes), icon: TrendingUp, tone: 'slate' },
        { label: 'EPPs por Vencer', value: formatNumber(values.eppsPorVencer), icon: Shield, tone: 'red' },
    ];
}

function KpiCard({ item }) {
    const Icon = item.icon;

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-slate-500">{item.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{item.value}</p>
                </div>
                <div className={`grid h-14 w-14 place-items-center rounded-xl ${toneClasses[item.tone]}`}>
                    <Icon className="h-7 w-7" />
                </div>
            </div>
        </div>
    );
}

function Panel({ title, subtitle, children }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-5">
                <h2 className="text-base font-bold text-slate-950">{title}</h2>
                {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
            </div>
            {children}
        </section>
    );
}

function EmptyChart({ text = 'Sin datos registrados' }) {
    return (
        <div className="flex h-72 items-center justify-center rounded-md border border-dashed border-slate-300 text-sm font-medium text-slate-500">
            {text}
        </div>
    );
}

export default function Dashboard({ dashboard = {} }) {
    const kpis = resolveKpis(dashboard.kpis);
    const inventarioFamilia = dashboard.inventarioFamilia ?? [];
    const consumoCentroCosto = dashboard.consumoCentroCosto ?? [];

    return (
        <AuthenticatedLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <p className="text-sm text-slate-500">Resumen general de almacen e inventario</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {kpis.map((item) => (
                        <KpiCard item={item} key={item.label} />
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Valor Inventario por Familia" subtitle="Distribucion actual">
                        {inventarioFamilia.length > 0 ? (
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={inventarioFamilia} dataKey="value" nameKey="name" innerRadius={70} outerRadius={105}>
                                            {inventarioFamilia.map((entry) => (
                                                <Cell fill={entry.color} key={entry.name} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value) => [formatCurrency(value), 'Valor']} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        ) : <EmptyChart text="Sin valorizacion de inventario registrada" />}
                    </Panel>

                    <Panel title="Consumo por Centro de Costo" subtitle="Mes actual">
                        {consumoCentroCosto.length > 0 ? (
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={consumoCentroCosto} layout="vertical">
                                        <CartesianGrid stroke="#e2e8f0" />
                                        <XAxis type="number" tickLine={false} tickFormatter={(value) => formatCurrency(value)} />
                                        <YAxis dataKey="centro" type="category" width={150} tickLine={false} />
                                        <Tooltip formatter={(value) => [formatCurrency(value), 'Consumo']} />
                                        <Bar dataKey="total" fill="#263845" radius={[0, 4, 4, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : <EmptyChart text="Sin consumo por centro de costo en el mes actual" />}
                    </Panel>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


