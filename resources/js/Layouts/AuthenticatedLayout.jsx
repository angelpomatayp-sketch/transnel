import Dropdown from '@/Components/Dropdown';
import { Link, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    Boxes,
    Building2,
    ChevronDown,
    ChevronRight,
    Check,
    CheckCheck,
    ClipboardList,
    FileOutput,
    Folder,
    Gauge,
    HardHat,
    Home,
    Menu,
    Package,
    Repeat2,
    Settings,
    Truck,
    UserCircle,
    Users,
    Warehouse,
    Wrench,
    X,
} from 'lucide-react';
import { useState } from 'react';

const groups = [
    {
        label: 'Dashboard',
        icon: Home,
        items: [{ label: 'Dashboard', icon: Gauge, href: 'dashboard', active: 'dashboard' }],
    },
    {
        label: 'Inventario',
        icon: Package,
        items: [
            { label: 'Catalogo productos', icon: Package, href: 'inventario.productos.index', active: 'inventario.productos.*', permission: 'productos.ver' },
            { label: 'Inventario', icon: Warehouse, href: 'inventario.inventario.index', active: 'inventario.inventario.*', permission: ['inventario.ver-stock', 'inventario.ver', 'stock.ver'] },
            { label: 'Movimientos', icon: Repeat2, href: 'inventario.movimientos.index', active: 'inventario.movimientos.*', permission: ['inventario.movimientos', 'movimientos.ver'] },
            { label: 'Kardex', icon: ClipboardList, href: 'inventario.kardex.index', active: 'inventario.kardex.*', permission: ['inventario.kardex', 'kardex.ver'] },
            { label: 'Familias', icon: Folder, href: 'inventario.familias.index', active: 'inventario.familias.*', permission: 'familias.ver' },
            { label: 'Unidades', icon: Boxes, href: 'inventario.unidades.index', active: 'inventario.unidades.*', permission: 'unidades.ver' },
        ],
    },
    {
        label: 'Requerimientos',
        icon: ClipboardList,
        items: [
            { label: 'Requerimientos', icon: ClipboardList, href: 'operaciones.requerimientos.index', active: 'operaciones.requerimientos.*', permission: 'requerimientos.ver' },
            { label: 'Vales de Salida', icon: FileOutput, href: 'operaciones.vales.index', active: 'operaciones.vales.*', permission: 'vales.ver' },
            { label: 'Prestamos', icon: Wrench, href: 'operaciones.prestamos.index', active: 'operaciones.prestamos.*', permission: 'prestamos.ver' },
            { label: 'Compras', icon: Truck, href: 'operaciones.compras.index', active: 'operaciones.compras.*', permission: 'compras.ver' },
        ],
    },
    {
        label: 'Administracion',
        icon: Settings,
        items: [
            { label: 'Usuarios', icon: Users, href: 'administracion.usuarios.index', active: 'administracion.usuarios.*', permission: 'usuarios.ver' },
            { label: 'Almacenes', icon: Warehouse, href: 'administracion.almacenes.index', active: 'administracion.almacenes.*', permission: 'almacenes.ver' },
            { label: 'Centros de Costo', icon: Building2, href: 'administracion.centros-costos.index', active: 'administracion.centros-costos.*', permission: 'centros-costos.ver' },
            { label: 'Trabajadores', icon: HardHat, href: 'administracion.trabajadores.index', active: 'administracion.trabajadores.*', permission: 'trabajadores.ver' },
            { label: 'Proveedores', icon: Truck, href: 'administracion.proveedores.index', active: 'administracion.proveedores.*', permission: 'proveedores.ver' },
        ],
    },
    {
        label: 'Reportes',
        icon: BarChart3,
        items: [
            { label: 'Reporte Logistico', icon: BarChart3, href: 'reportes.logistico.index', active: 'reportes.logistico.*', permission: 'reportes.ver' },
            { label: 'Auditoria Inventario', icon: BarChart3, href: 'reportes.auditoria-inventario.index', active: 'reportes.auditoria-inventario.*', permission: ['auditoria.ver', 'reportes.ver'] },
        ],
    },
];

function SidebarItem({ item, onNavigate }) {
    const active = isRouteActive(item.active);
    const Icon = item.icon;
    const content = (
        <span
            className={`flex h-10 items-center gap-3 rounded-md px-3 text-sm font-semibold transition ${
                active
                    ? 'bg-slate-100 text-brand-600'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'
            }`}
        >
            <Icon className="h-5 w-5 shrink-0 text-current" />
            <span className="truncate">{item.label}</span>
        </span>
    );

    if (item.href) {
        return <Link href={route(item.href)} onClick={onNavigate}>{content}</Link>;
    }

    return <button className="w-full cursor-default text-left opacity-80">{content}</button>;
}

function isRouteActive(patterns) {
    const activePatterns = Array.isArray(patterns) ? patterns : [patterns];

    return activePatterns.some((pattern) => pattern && route().current(pattern));
}

function groupHasActiveItem(group) {
    return group.items.some((item) => isRouteActive(item.active));
}

function canSee(item, permissions) {
    if (!item.permission || permissions.length === 0) {
        return true;
    }

    const expected = Array.isArray(item.permission) ? item.permission : [item.permission];

    return expected.some((permission) => permissions.includes(permission));
}

function uniqueItems(items) {
    const seen = new Set();

    return items.filter((item) => {
        const key = item.href ?? item.label;

        if (seen.has(key)) {
            return false;
        }

        seen.add(key);
        return true;
    });
}

export default function AuthenticatedLayout({
    title = 'Dashboard',
    headerTitle,
    headerSubtitle,
    header,
    children,
}) {
    const { auth, empresa } = usePage().props;
    const user = auth.user;
    const permissions = auth.permissions ?? [];
    const notifications = usePage().props.notifications ?? { total: 0, items: [] };
    const notificationTotal = Number(notifications.total ?? 0);
    const notificationLabel = notificationTotal > 99 ? '99+' : String(notificationTotal);
    const resolvedTitle = headerTitle ?? title;
    const resolvedSubtitle = headerSubtitle ?? empresa?.nombre ?? 'Logistica';
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState(() => {
        return Object.fromEntries(groups.map((group) => [group.label, groupHasActiveItem(group)]));
    });

    function visibleItems(group) {
        return uniqueItems(group.items.filter((item) => canSee(item, permissions)));
    }

    function toggleGroup(label) {
        setOpenGroups((current) => ({
            ...current,
            [label]: !current[label],
        }));
    }

    function markNotificationAsRead(key) {
        router.post(route('notifications.read'), { key }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function markAllNotificationsAsRead() {
        router.post(route('notifications.read'), { all: true }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    return (
        <div className="min-h-screen bg-brand-50 text-slate-900">
            <aside
                className={`fixed inset-y-0 left-0 z-40 w-64 border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-28 items-center justify-center border-b border-slate-200 bg-white px-4 shadow-sm">
                    <img
                        src="/logo.jpeg"
                        alt="Logistica DYM"
                        className="max-h-24 max-w-[12rem] object-contain"
                    />
                </div>

                <nav className="h-[calc(100vh-7rem)] overflow-y-auto px-3 pb-6 pt-3">
                    <div className="space-y-1">
                        {groups.map((group) => {
                            const items = visibleItems(group);
                            const groupActive = groupHasActiveItem(group);
                            const isOpen = openGroups[group.label];
                            const GroupIcon = group.icon;
                            const firstItem = items[0];

                            if (items.length === 0) {
                                return null;
                            }

                            if (group.label === 'Dashboard' && firstItem?.href) {
                                return (
                                    <Link
                                        href={route(firstItem.href)}
                                        key={group.label}
                                        onClick={() => setSidebarOpen(false)}
                                        className={`flex h-11 items-center gap-3 rounded-md px-4 text-sm font-semibold transition ${
                                            groupActive
                                                ? 'bg-accent-400 text-brand-700'
                                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950'
                                        }`}
                                    >
                                        <GroupIcon className="h-5 w-5 shrink-0" />
                                        {group.label}
                                    </Link>
                                );
                            }

                            return (
                                <div key={group.label}>
                                    <button
                                        type="button"
                                        onClick={() => toggleGroup(group.label)}
                                        className={`flex h-11 w-full items-center justify-between rounded-md px-4 text-left text-sm font-semibold transition ${
                                            groupActive
                                                ? 'bg-accent-400 text-brand-700'
                                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950'
                                        }`}
                                    >
                                        <span className="flex items-center gap-3">
                                            <GroupIcon className="h-5 w-5 shrink-0" />
                                            {group.label}
                                        </span>
                                        <ChevronRight className={`h-4 w-4 transition ${isOpen ? 'rotate-90' : ''}`} />
                                    </button>

                                    {isOpen && (
                                        <div className="mt-1 space-y-1 pl-7 pr-0">
                                            {items.map((item) => (
                                                <SidebarItem
                                                    item={item}
                                                    key={item.label}
                                                    onNavigate={() => setSidebarOpen(false)}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </nav>
            </aside>

            {sidebarOpen && (
                <button
                    aria-label="Cerrar menu"
                    className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex min-h-20 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 shadow-sm lg:px-8">
                    <div className="flex min-w-0 items-center gap-4">
                        <button
                            aria-label="Abrir menu"
                            className="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                            onClick={() => setSidebarOpen(true)}
                        >
                            {sidebarOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                        {header ?? (
                            <div className="min-w-0 py-3">
                                <h1 className="truncate text-xl font-semibold text-slate-950">{resolvedTitle}</h1>
                                <p className="truncate text-xs font-medium text-brand-300">{resolvedSubtitle}</p>
                            </div>
                        )}
                    </div>

                    <div className="flex shrink-0 items-center gap-2 sm:gap-4">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button className="relative rounded-md p-2 text-slate-600 transition hover:bg-slate-100" aria-label="Notificaciones">
                                    <Bell className="h-5 w-5" />
                                    {notificationTotal > 0 && (
                                        <span className="absolute -right-1 -top-1 rounded-md bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                            {notificationLabel}
                                        </span>
                                    )}
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content width="80" contentClasses="bg-white py-0">
                                <div className="w-80 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                                    {notificationTotal > 0 && (
                                        <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                            <span className="text-xs font-bold uppercase text-slate-500">Alertas</span>
                                            <button
                                                type="button"
                                                onClick={markAllNotificationsAsRead}
                                                className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                            >
                                                <CheckCheck className="h-4 w-4" />
                                                Marcar todo
                                            </button>
                                        </div>
                                    )}
                                    <div className="max-h-80 overflow-y-auto">
                                        {(notifications.items ?? []).length === 0 ? (
                                            <div className="px-4 py-6 text-center text-sm text-slate-500">
                                                No hay alertas pendientes.
                                            </div>
                                        ) : (
                                            notifications.items.map((item) => (
                                                <div
                                                    key={item.key}
                                                    className="border-b border-slate-100 px-4 py-3 last:border-b-0"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div>
                                                            <Link href={item.href} className="text-sm font-bold text-slate-900 hover:text-brand-500">
                                                                {item.title}
                                                            </Link>
                                                            <p className="mt-1 text-xs text-slate-500">{item.message}</p>
                                                        </div>
                                                        <span className="rounded-md bg-red-500 px-2 py-1 text-xs font-bold text-white">
                                                            {item.count > 99 ? '99+' : item.count}
                                                        </span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => markNotificationAsRead(item.key)}
                                                        className="mt-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                                    >
                                                        <Check className="h-4 w-4" />
                                                        Marcar como leido
                                                    </button>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </Dropdown.Content>
                        </Dropdown>

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button className="flex items-center gap-3 rounded-md px-2 py-1.5 text-left transition hover:bg-slate-100">
                                    <div className="hidden leading-tight sm:block">
                                        <div className="text-sm font-semibold text-slate-800">{user.name}</div>
                                        <div className="text-xs text-slate-500">
                                            {auth.roles?.[0] ?? 'usuario'}
                                        </div>
                                    </div>
                                    <UserCircle className="h-8 w-8 text-slate-500" />
                                    <ChevronDown className="h-4 w-4 text-slate-400" />
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>Perfil</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Cerrar sesion
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-[1600px] p-4 lg:p-8">{children}</main>
            </div>
        </div>
    );
}
