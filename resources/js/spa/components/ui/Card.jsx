export default function Card({ className = '', children }) {
    return (
        <div className={`overflow-hidden rounded-lg bg-white shadow ring-1 ring-gray-200 ${className}`}>
            {children}
        </div>
    );
}

export function CardHeader({ title, subtitle, actions }) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h3 className="text-base font-semibold text-gray-900">{title}</h3>
                {subtitle && <p className="mt-0.5 text-sm text-gray-500">{subtitle}</p>}
            </div>
            {actions && <div className="flex items-center gap-2">{actions}</div>}
        </div>
    );
}

export function CardBody({ className = '', children }) {
    return <div className={`px-5 py-4 ${className}`}>{children}</div>;
}
