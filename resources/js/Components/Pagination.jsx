import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ paginator }) {
    const links = paginator?.links ?? [];

    if (!links.length || Number(paginator.last_page ?? 1) <= 1) {
        return null;
    }

    function visit(url) {
        if (!url) {
            return;
        }

        router.visit(url, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function label(link, index) {
        if (index === 0) {
            return <ChevronLeft className="h-4 w-4" />;
        }

        if (index === links.length - 1) {
            return <ChevronRight className="h-4 w-4" />;
        }

        return link.label;
    }

    return (
        <div className="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
            <div className="font-medium text-slate-500">
                {paginator.total ?? 0} registros
            </div>
            <div className="flex flex-wrap items-center gap-2">
                {links.map((link, index) => (
                    <button
                        key={`${link.label}-${index}`}
                        type="button"
                        disabled={!link.url}
                        onClick={() => visit(link.url)}
                        className={`inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-35 ${
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                        }`}
                    >
                        {label(link, index)}
                    </button>
                ))}
            </div>
        </div>
    );
}
