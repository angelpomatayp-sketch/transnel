export default function ResourceTable({ columns, rows, empty = 'Sin registros', renderActions }) {
    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} className="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                    {column.label}
                                </th>
                            ))}
                            {renderActions && (
                                <th className="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Acciones
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                        {rows.length === 0 && (
                            <tr>
                                <td className="px-4 py-6 text-center text-slate-500" colSpan={columns.length + (renderActions ? 1 : 0)}>
                                    {empty}
                                </td>
                            </tr>
                        )}
                        {rows.map((row) => (
                            <tr key={row.id} className="transition hover:bg-slate-50">
                                {columns.map((column) => (
                                    <td key={column.key} className="px-4 py-3 align-middle text-slate-700">
                                        {column.render ? column.render(row) : row[column.key]}
                                    </td>
                                ))}
                                {renderActions && <td className="px-4 py-3 text-right">{renderActions(row)}</td>}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
