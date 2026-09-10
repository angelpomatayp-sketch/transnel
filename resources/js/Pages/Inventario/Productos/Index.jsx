import { ActionButton, AdminCard, SelectField, StatusBadge, TextField } from '@/Components/Admin/Card';
import AdminModal from '@/Components/Admin/AdminModal';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog';
import FlashMessage from '@/Components/Admin/FlashMessage';
import ResourceTable from '@/Components/Admin/ResourceTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { ChevronsLeft, ChevronsRight, ChevronLeft, ChevronRight, Download, Eye, ImagePlus, Pencil, Plus, Search, ShieldCheck, Trash2, Upload } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const emptyForm = {
    familia_id: '',
    unidad_medida_id: '',
    codigo: '',
    nombre: '',
    descripcion: '',
    marca: '',
    modelo: '',
    ubicacion: '',
    lote: '',
    stock_minimo: '0',
    es_epp: '0',
    vida_util_dias: '',
    dias_alerta_vencimiento: '',
    requiere_talla: '0',
    tallas_texto: '',
    activo: '1',
    imagenes: [],
};

export default function Index({ productos, familias, unidades, codigosSugeridos = {}, filters = {} }) {
    const [editing, setEditing] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [activeTab, setActiveTab] = useState('general');
    const [imagePreviews, setImagePreviews] = useState([]);
    const [viewer, setViewer] = useState(null);
    const [search, setSearch] = useState(filters.buscar ?? '');
    const [familyFilter, setFamilyFilter] = useState(filters.familia ?? '');
    const [perPage, setPerPage] = useState(String(filters.per_page ?? 10));
    const [confirmTarget, setConfirmTarget] = useState(null);
    const importRef = useRef(null);
    const { data, setData, post, delete: destroy, processing, errors, reset, clearErrors, transform } = useForm(emptyForm);
    const importForm = useForm({ archivo: null });
    const productRows = productos.data ?? productos;

    const columns = useMemo(() => [
        {
            key: 'imagen',
            label: 'Imagen',
            render: (row) => {
                const image = primaryImage(row);

                return (
                    <button
                        type="button"
                        onClick={() => row.imagenes?.length && openImageViewer(row.imagenes, row.nombre)}
                        disabled={!image}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-300"
                        title={image ? 'Ver imagen' : 'Sin imagen'}
                        aria-label={image ? `Ver imagen de ${row.nombre}` : 'Sin imagen'}
                    >
                        <Eye className="h-4 w-4" />
                    </button>
                );
            },
        },
        { key: 'codigo', label: 'Codigo' },
        { key: 'nombre', label: 'Nombre' },
        { key: 'familia', label: 'Familia', render: (row) => row.familia?.nombre ?? '-' },
        { key: 'unidad', label: 'Unidad', render: (row) => row.unidad?.abreviatura ?? row.unidad?.codigo ?? '-' },
        { key: 'stock_minimo', label: 'Minimo', render: (row) => Number(row.stock_minimo ?? 0).toFixed(0) },
        { key: 'activo', label: 'Estado', render: (row) => <StatusBadge active={row.activo} /> },
    ], []);

    useEffect(() => () => revokeBlobPreviews(imagePreviews), [imagePreviews]);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            router.get(route('inventario.productos.index'), {
                buscar: search || undefined,
                familia: familyFilter || undefined,
                per_page: perPage,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => window.clearTimeout(timer);
    }, [search, familyFilter, perPage]);

    function primaryImage(row) {
        if (!row) {
            return null;
        }

        return row.imagenes?.find((image) => image.principal) ?? row.imagenes?.[0] ?? null;
    }

    function normalizeImages(images = []) {
        return images.map((image, index) => ({
            src: image.ruta ?? image.src,
            title: image.nombre_original ?? image.title ?? `Imagen ${index + 1}`,
        })).filter((image) => image.src);
    }

    function openImageViewer(images = [], title = 'Producto', index = 0) {
        const normalized = normalizeImages(images);

        if (normalized.length === 0) {
            return;
        }

        setViewer({ images: normalized, index, title });
    }

    function revokeBlobPreviews(previews) {
        previews.forEach((preview) => {
            if (preview.src?.startsWith('blob:')) {
                URL.revokeObjectURL(preview.src);
            }
        });
    }

    function updateImagePreviews(files, fallback = []) {
        const selectedFiles = Array.from(files ?? []).slice(0, 4);

        revokeBlobPreviews(imagePreviews);
        setData('imagenes', selectedFiles);

        if (selectedFiles.length > 0) {
            setImagePreviews(selectedFiles.map((file) => ({
                src: URL.createObjectURL(file),
                title: file.name,
            })));
            return;
        }

        setImagePreviews(normalizeImages(fallback));
    }

    function closeModal() {
        setModalOpen(false);
        setEditing(null);
        updateImagePreviews([], []);
        clearErrors();
        reset();
    }

    function startCreate() {
        setEditing(null);
        clearErrors();
        reset();
        setData(emptyForm);
        updateImagePreviews([], []);
        setActiveTab('general');
        setModalOpen(true);
    }

    function startEdit(row) {
        setEditing(row);
        clearErrors();
        setData({
            familia_id: row.familia_id ?? '',
            unidad_medida_id: row.unidad_medida_id ?? '',
            codigo: row.codigo ?? '',
            nombre: row.nombre ?? '',
            descripcion: row.descripcion ?? '',
            marca: row.marca ?? '',
            modelo: row.modelo ?? '',
            ubicacion: row.ubicacion ?? '',
            lote: row.lote ?? '',
            stock_minimo: Number(row.stock_minimo ?? 0).toFixed(0),
            es_epp: row.es_epp ? '1' : '0',
            vida_util_dias: row.vida_util_dias ?? '',
            dias_alerta_vencimiento: row.dias_alerta_vencimiento ?? '',
            requiere_talla: row.requiere_talla ? '1' : '0',
            tallas_texto: row.tallas_disponibles?.join(', ') ?? '',
            activo: row.activo ? '1' : '0',
            imagenes: [],
        });
        updateImagePreviews([], row.imagenes ?? []);
        setActiveTab('general');
        setModalOpen(true);
    }

    function nextCodeForFamily(familiaId) {
        return codigosSugeridos[familiaId] ?? '';
    }

    function selectedFamily() {
        return familias.find((item) => String(item.id) === String(data.familia_id));
    }

    function isEppFamily() {
        return selectedFamily()?.es_epp === true;
    }

    function handleFamilyChange(value) {
        const familia = familias.find((item) => String(item.id) === String(value));
        const familyIsEpp = familia?.es_epp === true;

        setData({
            ...data,
            familia_id: value,
            codigo: editing ? data.codigo : nextCodeForFamily(value),
            es_epp: familyIsEpp ? '1' : '0',
            vida_util_dias: familyIsEpp ? (data.vida_util_dias || '365') : '',
            dias_alerta_vencimiento: familyIsEpp ? (data.dias_alerta_vencimiento || '30') : '',
            requiere_talla: familyIsEpp ? data.requiere_talla : '0',
            tallas_texto: familyIsEpp ? data.tallas_texto : '',
        });
    }

    function toggleRequiresSize() {
        setData('requiere_talla', data.requiere_talla === '1' ? '0' : '1');
    }

    function pageUrl(page) {
        if (!page || page < 1 || page > productos.last_page) {
            return null;
        }

        const url = new URL(productos.path);
        url.searchParams.set('page', page);
        if (search) url.searchParams.set('buscar', search);
        if (familyFilter) url.searchParams.set('familia', familyFilter);
        url.searchParams.set('per_page', perPage);

        return `${url.pathname}${url.search}`;
    }

    function visitPage(page) {
        const url = pageUrl(page);

        if (url) {
            router.visit(url, { preserveScroll: true, preserveState: true });
        }
    }

    function submit(e) {
        e.preventDefault();
        const options = { preserveScroll: true, forceFormData: true, onSuccess: closeModal };

        if (editing) {
            transform((formData) => ({ ...formData, _method: 'put' }));
            post(route('inventario.productos.update', editing.id), options);
            transform((formData) => formData);
            return;
        }

        transform((formData) => formData);
        post(route('inventario.productos.store'), options);
    }

    function submitImport(file) {
        if (!file) return;
        importForm.setData('archivo', file);
        router.post(route('inventario.productos.import'), { archivo: file }, { forceFormData: true, preserveScroll: true });
        if (importRef.current) importRef.current.value = '';
    }

    function confirmDestroy() {
        if (!confirmTarget) return;
        destroy(route('inventario.productos.destroy', confirmTarget.id), {
            preserveScroll: true,
            onFinish: () => setConfirmTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Productos">
            <Head title="Productos" />
            <div className="space-y-5">
                <FlashMessage />
                <AdminCard
                    title="Listado"
                    filters={
                        <div className="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                            <label className="relative block w-full sm:w-72">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar producto..."
                                    className="h-10 w-full rounded-md border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                />
                            </label>
                            <select
                                value={familyFilter}
                                onChange={(e) => setFamilyFilter(e.target.value)}
                                className="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100 sm:w-64"
                                aria-label="Filtrar por familia"
                            >
                                <option value="">Todas las familias</option>
                                {familias.map((familia) => (
                                    <option key={familia.id} value={familia.id}>
                                        {familia.nombre}
                                    </option>
                                ))}
                            </select>
                        </div>
                    }
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <ActionButton type="button" tone="secondary" onClick={startCreate}><Plus className="mr-2 h-4 w-4" /> Nuevo</ActionButton>
                            <a href={route('inventario.productos.export')} className="inline-flex h-10 items-center rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <Download className="mr-2 h-4 w-4" /> Exportar
                            </a>
                            <input ref={importRef} type="file" accept=".xlsx,.xls,.csv" className="hidden" onChange={(e) => submitImport(e.target.files?.[0])} />
                            <ActionButton type="button" tone="secondary" onClick={() => importRef.current?.click()}><Upload className="mr-2 h-4 w-4" /> Importar</ActionButton>
                        </div>
                    }
                >
                    <ResourceTable columns={columns} rows={productRows} renderActions={(row) => (
                        <div className="inline-flex gap-2"><ActionButton type="button" tone="secondary" onClick={() => startEdit(row)}><Pencil className="h-4 w-4" /></ActionButton><ActionButton type="button" tone="danger" onClick={() => setConfirmTarget(row)}><Trash2 className="h-4 w-4" /></ActionButton></div>
                    )} />
                    {productos.links && (
                        <div className="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-600 lg:flex-row lg:items-center lg:justify-between">
                            <div className="text-sm font-medium text-slate-500">
                                {productos.total ?? 0} registros
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <button type="button" onClick={() => visitPage(1)} disabled={productos.current_page === 1} className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-35" aria-label="Primera pagina">
                                    <ChevronsLeft className="h-4 w-4" />
                                </button>
                                <button type="button" onClick={() => visitPage(productos.current_page - 1)} disabled={productos.current_page === 1} className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-35" aria-label="Pagina anterior">
                                    <ChevronLeft className="h-4 w-4" />
                                </button>

                                {Array.from({ length: productos.last_page }, (_, index) => index + 1)
                                    .filter((page) => productos.last_page <= 7 || page === 1 || page === productos.last_page || Math.abs(page - productos.current_page) <= 1)
                                    .map((page, index, pages) => (
                                        <span key={page} className="inline-flex items-center gap-2">
                                            {index > 0 && page - pages[index - 1] > 1 && <span className="px-1 text-slate-400">...</span>}
                                            <button
                                                type="button"
                                                onClick={() => visitPage(page)}
                                                className={`inline-flex h-9 min-w-9 items-center justify-center rounded-full px-3 text-sm font-semibold transition ${
                                                    page === productos.current_page
                                                        ? 'bg-sky-100 text-sky-700'
                                                        : 'text-slate-600 hover:bg-slate-100'
                                                }`}
                                            >
                                                {page}
                                            </button>
                                        </span>
                                    ))}

                                <button type="button" onClick={() => visitPage(productos.current_page + 1)} disabled={productos.current_page === productos.last_page} className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-35" aria-label="Pagina siguiente">
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                                <button type="button" onClick={() => visitPage(productos.last_page)} disabled={productos.current_page === productos.last_page} className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-35" aria-label="Ultima pagina">
                                    <ChevronsRight className="h-4 w-4" />
                                </button>

                                <select
                                    value={perPage}
                                    onChange={(e) => setPerPage(e.target.value)}
                                    className="ml-1 h-10 rounded-md border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                    aria-label="Registros por pagina"
                                >
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                    )}
                </AdminCard>
                <AdminModal show={modalOpen} onClose={closeModal} title={editing ? 'Editar producto' : 'Nuevo producto'} formId="producto-form" submitLabel={editing ? 'Actualizar' : 'Guardar'} processing={processing} maxWidth="2xl">
                    <form id="producto-form" onSubmit={submit} className="space-y-3">
                        <div className="border-b border-slate-200">
                            <div className="flex gap-4">
                                {[
                                    ['general', 'Informacion General'],
                                    ['ubicacion', 'Ubicacion'],
                                ].map(([key, label]) => (
                                    <button
                                        type="button"
                                        key={key}
                                        onClick={() => setActiveTab(key)}
                                        className={`border-b-2 px-4 py-2 text-sm font-bold transition ${
                                            activeTab === key
                                                ? 'border-accent-400 text-brand-700'
                                                : 'border-transparent text-slate-500 hover:text-slate-800'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {activeTab === 'general' && (
                            <div className="space-y-3">
                                <div className="grid gap-3 md:grid-cols-[240px_1fr]">
                                    <TextField label="Codigo" value={data.codigo || 'Se genera automaticamente'} readOnly disabled error={errors.codigo} />
                                    <SelectField label="Familia" value={data.familia_id} onChange={(e) => handleFamilyChange(e.target.value)} error={errors.familia_id}>
                                        <option value="">Seleccione</option>{familias.map((familia) => <option key={familia.id} value={familia.id}>{familia.codigo} - {familia.nombre}</option>)}
                                    </SelectField>
                                    <div className="md:col-span-2">
                                        <TextField label="Nombre del producto" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} error={errors.nombre} placeholder="Nombre descriptivo del producto" />
                                    </div>
                                    <label className="block md:col-span-2">
                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripcion</span>
                                        <textarea
                                            value={data.descripcion}
                                            onChange={(e) => setData('descripcion', e.target.value)}
                                            placeholder="Descripcion detallada (opcional)"
                                            rows={2}
                                            className="mt-1 min-h-14 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100"
                                        />
                                        {errors.descripcion && <p className="mt-1 text-xs font-medium text-red-600">{errors.descripcion}</p>}
                                    </label>
                                </div>

                                <div className="grid gap-3 md:grid-cols-[170px_1fr]">
                                    <SelectField label="Unidad" value={data.unidad_medida_id} onChange={(e) => setData('unidad_medida_id', e.target.value)} error={errors.unidad_medida_id}>
                                        <option value="">Seleccione</option>{unidades.map((unidad) => <option key={unidad.id} value={unidad.id}>{unidad.codigo} - {unidad.nombre}</option>)}
                                    </SelectField>
                                    <TextField label="Marca" value={data.marca} onChange={(e) => setData('marca', e.target.value)} error={errors.marca} placeholder="Ej: 3M, CAT, Stanley" />
                                    <TextField label="Modelo" value={data.modelo} onChange={(e) => setData('modelo', e.target.value)} error={errors.modelo} placeholder="Modelo o referencia" />
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <TextField label="Stock minimo" type="number" step="1" min="0" value={data.stock_minimo} onChange={(e) => setData('stock_minimo', e.target.value)} error={errors.stock_minimo} />
                                </div>

                                {isEppFamily() && (
                                    <div className="rounded-lg border border-brand-700 bg-slate-50 p-3">
                                        <div className="mb-3 flex items-center gap-3">
                                            <ShieldCheck className="h-5 w-5 text-brand-700" />
                                            <h3 className="text-base font-bold text-brand-700">Configuracion de EPP</h3>
                                            {selectedFamily()?.categoria_epp && (
                                                <span className="rounded-md bg-orange-100 px-2.5 py-1 text-xs font-bold uppercase text-orange-700">
                                                    {selectedFamily().categoria_epp}
                                                </span>
                                            )}
                                        </div>

                                        <div className="grid gap-3 md:grid-cols-2">
                                            <TextField label="Vida util dias" type="number" value={data.vida_util_dias} onChange={(e) => setData('vida_util_dias', e.target.value)} error={errors.vida_util_dias} />
                                            <TextField label="Dias para alerta" type="number" value={data.dias_alerta_vencimiento} onChange={(e) => setData('dias_alerta_vencimiento', e.target.value)} error={errors.dias_alerta_vencimiento} />
                                        </div>

                                        <button type="button" onClick={toggleRequiresSize} className="mt-3 flex items-center gap-3 text-sm font-medium text-slate-700">
                                            <span className={`flex h-6 w-11 items-center rounded-full p-1 transition ${data.requiere_talla === '1' ? 'bg-accent-400' : 'bg-slate-300'}`}>
                                                <span className={`h-4 w-4 rounded-full bg-white transition ${data.requiere_talla === '1' ? 'translate-x-5' : ''}`} />
                                            </span>
                                            Este EPP requiere talla
                                        </button>

                                        {data.requiere_talla === '1' && (
                                            <div className="mt-3">
                                                <TextField label="Tallas disponibles" value={data.tallas_texto} onChange={(e) => setData('tallas_texto', e.target.value)} error={errors.tallas_texto} placeholder="Ej: S, M, L, XL o 38, 39, 40, 41" />
                                                <p className="mt-1 text-xs text-slate-500">Separar tallas con comas.</p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {activeTab === 'ubicacion' && (
                            <div className="grid gap-4 md:grid-cols-[210px_1fr]">
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div className="grid grid-cols-2 gap-2">
                                        {imagePreviews.length > 0 ? (
                                            imagePreviews.map((image, index) => (
                                                <button
                                                    key={`${image.src}-${index}`}
                                                    type="button"
                                                    onClick={() => openImageViewer(imagePreviews, data.nombre || 'Producto', index)}
                                                    className="group relative flex h-24 w-full items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-white"
                                                    title="Ver imagen"
                                                >
                                                    <img
                                                        src={image.src}
                                                        alt={image.title}
                                                        className="h-full w-full object-contain p-1 transition group-hover:scale-105"
                                                    />
                                                    <span className="absolute right-1 top-1 rounded bg-black/55 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                                        {index + 1}
                                                    </span>
                                                </button>
                                            ))
                                        ) : (
                                            <div className="col-span-2 flex h-28 items-center justify-center rounded-md border border-dashed border-slate-300 bg-white">
                                                <ImagePlus className="h-10 w-10 text-slate-400" />
                                            </div>
                                        )}
                                    </div>
                                    <label className="mt-3 block">
                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Imagenes del producto</span>
                                        <input type="file" accept="image/*" multiple onChange={(e) => updateImagePreviews(e.target.files, editing?.imagenes ?? [])} className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" />
                                        <span className="mt-1 block text-xs text-slate-500">Maximo 4 imagenes. La primera sera la principal.</span>
                                        {errors.imagenes && <p className="mt-1 text-xs font-medium text-red-600">{errors.imagenes}</p>}
                                        {errors['imagenes.0'] && <p className="mt-1 text-xs font-medium text-red-600">{errors['imagenes.0']}</p>}
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => openImageViewer(imagePreviews, data.nombre || 'Producto')}
                                        disabled={imagePreviews.length === 0}
                                        className="mt-3 inline-flex h-9 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Ver imagenes
                                    </button>
                                </div>

                                <div className="grid content-start gap-3 md:grid-cols-2">
                                    <TextField label="Ubicacion" value={data.ubicacion} onChange={(e) => setData('ubicacion', e.target.value)} error={errors.ubicacion} placeholder="Rack, almacen o zona" />
                                    <TextField label="Lote" value={data.lote} onChange={(e) => setData('lote', e.target.value)} error={errors.lote} />
                                    <SelectField label="Estado" value={data.activo} onChange={(e) => setData('activo', e.target.value)} error={errors.activo}><option value="1">Activo</option><option value="0">Inactivo</option></SelectField>
                                </div>
                            </div>
                        )}
                    </form>
                </AdminModal>
                <AdminModal show={Boolean(viewer)} onClose={() => setViewer(null)} title={viewer?.title ?? 'Imagen del producto'} maxWidth="3xl">
                    {viewer && (
                        <div className="space-y-3">
                            <div className="relative flex h-[460px] max-h-[60vh] items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                {viewer.images.length > 1 && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => setViewer((current) => ({ ...current, index: (current.index - 1 + current.images.length) % current.images.length }))}
                                            className="absolute left-3 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow hover:bg-white"
                                            aria-label="Imagen anterior"
                                        >
                                            <ChevronLeft className="h-5 w-5" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setViewer((current) => ({ ...current, index: (current.index + 1) % current.images.length }))}
                                            className="absolute right-3 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow hover:bg-white"
                                            aria-label="Imagen siguiente"
                                        >
                                            <ChevronRight className="h-5 w-5" />
                                        </button>
                                    </>
                                )}
                            <img
                                src={viewer.images[viewer.index].src}
                                alt={viewer.images[viewer.index].title}
                                className="h-full w-full object-contain p-3"
                            />
                            </div>
                            <div className="flex items-center justify-between text-sm font-semibold text-slate-600">
                                <span>{viewer.images[viewer.index].title}</span>
                                <span>{viewer.index + 1} / {viewer.images.length}</span>
                            </div>
                        </div>
                    )}
                </AdminModal>
                <ConfirmDialog
                    show={Boolean(confirmTarget)}
                    title="Desactivar producto"
                    message={confirmTarget ? `Se desactivara el producto ${confirmTarget.nombre}.` : ''}
                    confirmText="Desactivar"
                    tone="danger"
                    processing={processing}
                    onConfirm={confirmDestroy}
                    onCancel={() => setConfirmTarget(null)}
                />
            </div>
        </AuthenticatedLayout>
    );
}


