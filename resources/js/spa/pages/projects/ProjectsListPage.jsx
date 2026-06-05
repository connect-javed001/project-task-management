import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import EmptyState from '../../components/ui/EmptyState';
import { ProjectStatusBadge } from '../../components/ui/Badge';
import { Field, Select, TextInput } from '../../components/ui/Field';
import { useAuth } from '../../context/AuthContext';
import { listProjects } from '../../api/projects';
import { formatDate, formatErrors } from '../../lib/format';

const STATUS_OPTIONS = ['', 'planning', 'active', 'completed', 'archived'];

export default function ProjectsListPage() {
    const { isAdmin } = useAuth();
    const canCreate = isAdmin;

    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ status: '', date_from: '', date_to: '' });
    const [page, setPage] = useState(1);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        const params = { page, per_page: 10 };
        if (filters.status) params.status = filters.status;
        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        listProjects(params)
            .then((res) => mounted && setData(res))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [page, filters.status, filters.date_from, filters.date_to]);

    const onFilterChange = (key) => (e) => {
        setPage(1);
        setFilters((f) => ({ ...f, [key]: e.target.value }));
    };

    const projects = data?.data ?? [];

    return (
        <div>
            <PageHeader
                title="Projects"
                subtitle="Browse and manage your projects"
                actions={
                    canCreate && (
                        <Link to="/projects/new">
                            <Button>New project</Button>
                        </Link>
                    )
                }
            />

            <Card className="mb-4">
                <div className="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-3">
                    <Field label="Status" htmlFor="status">
                        <Select id="status" value={filters.status} onChange={onFilterChange('status')}>
                            {STATUS_OPTIONS.map((s) => (
                                <option key={s} value={s}>
                                    {s || 'All statuses'}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Start date from" htmlFor="date_from">
                        <TextInput
                            id="date_from"
                            type="date"
                            value={filters.date_from}
                            onChange={onFilterChange('date_from')}
                        />
                    </Field>
                    <Field label="End date to" htmlFor="date_to">
                        <TextInput
                            id="date_to"
                            type="date"
                            value={filters.date_to}
                            onChange={onFilterChange('date_to')}
                        />
                    </Field>
                </div>
            </Card>

            {loading && <div className="text-sm text-gray-500">Loading projects…</div>}

            {!loading && projects.length === 0 && (
                <EmptyState
                    title="No projects found"
                    description={
                        canCreate
                            ? 'Get started by creating your first project.'
                            : 'No projects are assigned to you yet.'
                    }
                    action={
                        canCreate && (
                            <Link to="/projects/new">
                                <Button>Create project</Button>
                            </Link>
                        )
                    }
                />
            )}

            {!loading && projects.length > 0 && (
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-5 py-3 text-left">Name</th>
                                    <th className="px-5 py-3 text-left">Status</th>
                                    <th className="px-5 py-3 text-left">Manager</th>
                                    <th className="px-5 py-3 text-left">Dates</th>
                                    <th className="px-5 py-3 text-left">Tasks</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {projects.map((p) => (
                                    <tr key={p.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3">
                                            <Link
                                                to={`/projects/${p.id}`}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                {p.name}
                                            </Link>
                                            {p.description && (
                                                <p className="mt-0.5 line-clamp-1 text-xs text-gray-500">
                                                    {p.description}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-5 py-3">
                                            <ProjectStatusBadge status={p.status} />
                                        </td>
                                        <td className="px-5 py-3 text-gray-700">
                                            {p.assigned_manager?.name ?? '—'}
                                        </td>
                                        <td className="px-5 py-3 text-gray-600">
                                            {formatDate(p.start_date)} – {formatDate(p.end_date)}
                                        </td>
                                        <td className="px-5 py-3 text-gray-700">
                                            {p.tasks?.length ?? 0}
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
