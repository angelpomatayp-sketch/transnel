export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-slate-300 text-accent-400 shadow-sm focus:ring-accent-400 ' +
                className
            }
        />
    );
}
