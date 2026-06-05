import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import PageHeader from '../components/PageHeader';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import EmptyState from '../components/ui/EmptyState';
import { Field, TextInput } from '../components/ui/Field';
import { listActivityLogs } from '../api/activityLogs';
import { formatDateTime, formatErrors } from '../lib/format';

export default function ActivityLogPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        user_id: '',
        action: '',
        entity_type: '',
        date_from: '',
        date_to: '',
    });
    const [page, setPage] = useState(1);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        const params = { page, per_page: 25 };
        Object.entries(filters).forEach(([k, v]) => {
            if (v) params[k] = v;
        });
        listActivityLogs(params)
            .then((res) => mounted && setData(res))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [page, filters.user_id, filters.action, filters.entity_type, filters.date_from, filters.date_to]);

    const onFilterChange = (key) => (e) => {
        setPage(1);
        setFilters((f) => ({ ...f, [key]: e.target.value }));
    };

    const logs = data?.data ?? [];

    return (
        <div>
            <PageHeader title="Activity log" subtitle="Audit trail of all system activity" />

            <Card className="mb-4">
                <div className="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-5">
                    <Field label="User ID" htmlFor="user_id">
                        <TextInput
                            id="user_id"
                            type="number"
                            min="1"
                            value={filters.user_id}
                            onChange={onFilterChange('user_id')}
                        />
                    </Field>
                    <Field label="Action" htmlFor="action">
                        <TextInput
                            id="action"
                            placeholder="created, updated…"
                            value={filters.action}
                            onChange={onFilterChange('action')}
                        />
                    </Field>
                    <Field label="Entity" htmlFor="entity_type">
                        <TextInput
                            id="entity_type"
                            placeholder="Project, Task…"
                            value={filters.entity_type}
                            onChange={onFilterChange('entity_type')}
                        />
                    </Field>
                    <Field label="From" htmlFor="date_from">
                        <TextInput
                            id="date_from"
                            type="date"
                            value={filters.date_from}
                            onChange={onFilterChange('date_from')}
                        />
                    </Field>
                    <Field label="To" htmlFor="date_to">
                        <TextInput
                            id="date_to"
                            type="date"
                            value={filters.date_to}
                            onChange={onFilterChange('date_to')}
                        />
                    </Field>
                </div>
            </Card>

            {loading && <div className="text-sm text-gray-500">Loading activity…</div>}

            {!loading && logs.length === 0 && (
                <EmptyState
                    title="No activity"
                    description="No log entries match your filters."
                />
            )}

            {!loading && logs.length > 0 && (
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3 text-left">When</th>
                                    <th className="px-4 py-3 text-left">User</th>
                                    <th className="px-4 py-3 text-left">Action</th>
                                    <th className="px-4 py-3 text-left">Entity</th>
                                    <th className="px-4 py-3 text-left">Changes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {logs.map((l) => (
                                    <tr key={l.id} className="align-top hover:bg-gray-50">
                                        <td className="px-4 py-3 text-gray-600">
                                            {formatDateTime(l.created_at)}
                                        </td>
                                        <td className="px-4 py-3 text-gray-800">
                                            {l.user?.name ?? `#${l.user_id ?? '—'}`}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs text-gray-700">
                                            {l.action}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700">
                                            {l.entity_type}
                                            {l.entity_id && (
                                                <span className="text-gray-400"> #{l.entity_id}</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-xs text-gray-600">
                                            <Diff prev={l.previous_value} next={l.new_value} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {data && data.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-gray-600">
                    <span>
                        Page {data.current_page} of {data.last_page} · {data.total} total
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={data.current_page <= 1}
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={data.current_page >= data.last_page}
                            onClick={() => setPage((p) => p + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

function Diff({ prev, next }) {
    if (!prev && !next) return <span className="text-gray-400">—</span>;
    return (
        <details>
            <summary className="cursor-pointer text-indigo-600 hover:text-indigo-800">
                view
            </summary>
            <div className="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <pre className="overflow-x-auto rounded bg-red-50 p-2 text-[11px] text-red-800">
                    {prev ? JSON.stringify(prev, null, 2) : '—'}
                </pre>
                <pre className="overflow-x-auto rounded bg-green-50 p-2 text-[11px] text-green-800">
                    {next ? JSON.stringify(next, null, 2) : '—'}
                </pre>
            </div>
        </details>
    );
}
