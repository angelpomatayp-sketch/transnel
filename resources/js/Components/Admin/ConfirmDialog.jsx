import Modal from '@/Components/Modal';
import { AlertTriangle, CheckCircle2, Info, Trash2, X } from 'lucide-react';

const toneStyles = {
    danger: {
        iconWrap: 'bg-red-50 text-red-600',
        confirm: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-200',
        icon: Trash2,
    },
    warning: {
        iconWrap: 'bg-amber-50 text-amber-600',
        confirm: 'bg-amber-500 text-slate-950 hover:bg-amber-400 focus:ring-amber-200',
        icon: AlertTriangle,
    },
    success: {
        iconWrap: 'bg-emerald-50 text-emerald-600',
        confirm: 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-200',
        icon: CheckCircle2,
    },
    info: {
        iconWrap: 'bg-sky-50 text-sky-600',
        confirm: 'bg-brand-700 text-white hover:bg-brand-800 focus:ring-brand-200',
        icon: Info,
    },
};

export default function ConfirmDialog({
    show,
    title = 'Confirmar accion',
    message,
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    tone = 'warning',
    processing = false,
    onConfirm,
    onCancel,
}) {
    const styles = toneStyles[tone] ?? toneStyles.warning;
    const Icon = styles.icon;

    return (
        <Modal show={show} maxWidth="md" onClose={onCancel}>
            <div className="relative overflow-hidden bg-white">
                <button
                    type="button"
                    onClick={onCancel}
                    className="absolute right-4 top-4 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Cerrar"
                >
                    <X className="h-5 w-5" />
                </button>

                <div className="px-6 pb-5 pt-6">
                    <div className="flex gap-4">
                        <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-md ${styles.iconWrap}`}>
                            <Icon className="h-5 w-5" />
                        </div>
                        <div className="min-w-0 pr-8">
                            <h2 className="text-lg font-bold text-slate-950">{title}</h2>
                            {message && <p className="mt-2 text-sm leading-6 text-slate-600">{message}</p>}
                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={processing}
                        className="inline-flex h-10 items-center rounded-md border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                    >
                        {cancelText}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className={`inline-flex h-10 items-center rounded-md px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 disabled:opacity-60 ${styles.confirm}`}
                    >
                        {processing ? 'Procesando...' : confirmText}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
