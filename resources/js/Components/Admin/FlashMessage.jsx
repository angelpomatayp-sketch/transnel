import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

export default function FlashMessage() {
    const { flash, errors } = usePage().props;
    const firstError = errors ? Object.values(errors)[0] : null;
    const normalizedError = Array.isArray(firstError) ? firstError[0] : firstError;
    const message = flash?.success ?? flash?.error ?? normalizedError;
    const type = flash?.success ? 'success' : 'error';
    const [visible, setVisible] = useState(Boolean(message));

    const styles = useMemo(() => ({
        success: {
            wrapper: 'border-emerald-200 bg-emerald-50 text-emerald-800 shadow-emerald-900/10',
            icon: 'text-emerald-600',
        },
        error: {
            wrapper: 'border-red-200 bg-red-50 text-red-800 shadow-red-900/10',
            icon: 'text-red-600',
        },
    }), []);

    useEffect(() => {
        if (!message) {
            setVisible(false);
            return undefined;
        }

        setVisible(true);
        const timer = window.setTimeout(() => setVisible(false), 3500);

        return () => window.clearTimeout(timer);
    }, [message, type]);

    if (!message || !visible) {
        return null;
    }

    const Icon = type === 'success' ? CheckCircle2 : AlertCircle;

    return (
        <div
            className={`fixed right-4 top-24 z-50 flex w-80 max-w-[calc(100vw-2rem)] items-start gap-3 rounded-md border px-3 py-3 text-sm font-semibold shadow-lg ${styles[type].wrapper}`}
            role="status"
        >
            <Icon className={`mt-0.5 h-4 w-4 shrink-0 ${styles[type].icon}`} />
            <span className="min-w-0 flex-1">{message}</span>
            <button
                type="button"
                onClick={() => setVisible(false)}
                className="rounded p-0.5 text-current opacity-70 hover:bg-white/70 hover:opacity-100"
                aria-label="Cerrar notificacion"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}
