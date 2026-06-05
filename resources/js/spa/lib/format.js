export function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export function formatDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function formatErrors(error) {
    const data = error?.response?.data;
    if (!data) return { message: error?.message ?? 'Something went wrong', fields: {} };
    if (data.errors) {
        const fields = Object.fromEntries(
            Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]),
        );
        return { message: data.message ?? 'Validation failed', fields };
    }
    return { message: data.message ?? 'Request failed', fields: {} };
}
