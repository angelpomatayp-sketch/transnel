export function AdminCard({ title, description, children, actions, filters }) {
    return (
        <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-5">
                    <div className="min-w-0">
                        <h2 className="text-base font-bold text-slate-950">{title}</h2>
                        {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                    </div>
                    {filters}
                </div>
                {actions}
            </div>
            <div className="p-5">{children}</div>
        </section>
    );
}

export function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span>
            <div className="mt-1">{children}</div>
            {error && <p className="mt-1 text-xs font-medium text-red-600">{error}</p>}
        </label>
    );
}

export function TextField({ label, error, ...props }) {
    return (
        <Field label={label} error={error}>
            <input
                {...props}
                className="dym-focus h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
            />
        </Field>
    );
}

export function SelectField({ label, error, children, ...props }) {
    return (
        <Field label={label} error={error}>
            <select
                {...props}
                className="dym-focus h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
            >
                {children}
            </select>
        </Field>
    );
}

export function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
            }`}
        >
            {active ? 'Activo' : 'Inactivo'}
        </span>
    );
}

export function ActionButton({ children, tone = 'primary', ...props }) {
    const tones = {
        primary: 'bg-brand-700 text-white hover:bg-brand-800',
        secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        danger: 'bg-red-600 text-white hover:bg-red-700',
    };

    return (
        <button
            {...props}
            className={`inline-flex h-10 items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 ${tones[tone]}`}
        >
            {children}
        </button>
    );
}
