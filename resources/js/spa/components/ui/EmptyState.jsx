export default function EmptyState({ title, description, action }) {
    return (
        <div className="rounded-lg border-2 border-dashed border-gray-200 px-6 py-12 text-center">
            <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
            {description && <p className="mt-1 text-sm text-gray-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
