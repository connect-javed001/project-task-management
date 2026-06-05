export function Field({ label, htmlFor, error, hint, required, children }) {
    return (
        <div>
            {label && (
                <label htmlFor={htmlFor} className="block text-sm font-medium text-gray-700">
                    {label}
                    {required && <span className="ml-0.5 text-red-500">*</span>}
                </label>
            )}
            <div className="mt-1">{children}</div>
            {hint && !error && <p className="mt-1 text-xs text-gray-500">{hint}</p>}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

const baseInput =
    'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500';

export function TextInput({ className = '', ...props }) {
    return <input className={`${baseInput} ${className}`} {...props} />;
}

export function Textarea({ className = '', rows = 3, ...props }) {
    return <textarea rows={rows} className={`${baseInput} ${className}`} {...props} />;
}

export function Select({ className = '', children, ...props }) {
    return (
        <select className={`${baseInput} ${className}`} {...props}>
            {children}
        </select>
    );
}
