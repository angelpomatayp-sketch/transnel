import Modal from '@/Components/Modal';
import { ActionButton } from '@/Components/Admin/Card';
import { X } from 'lucide-react';

export default function AdminModal({
    show,
    title,
    description,
    children,
    onClose,
    processing = false,
    submitLabel = 'Guardar',
    maxWidth = '2xl',
    formId,
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth={maxWidth}>
            <div className="border-b border-slate-200 px-5 py-3">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="text-lg font-bold text-slate-950">{title}</h2>
                        {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                        aria-label="Cerrar"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div className="max-h-[72vh] overflow-y-auto px-5 py-4">
                {children}
            </div>

            {formId && (
                <div className="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <ActionButton type="button" tone="secondary" onClick={onClose}>
                        Cancelar
                    </ActionButton>
                    <ActionButton type="submit" form={formId} disabled={processing}>
                        {submitLabel}
                    </ActionButton>
                </div>
            )}
        </Modal>
    );
}
