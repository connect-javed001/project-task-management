import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import EmptyState from '../../components/ui/EmptyState';
import { PriorityBadge, TaskStatusBadge } from '../../components/ui/Badge';
import { Field, Select, TextInput } from '../../components/ui/Field';
import { useAuth } from '../../context/AuthContext';
import { listTasks } from '../../api/tasks';
import { formatDateTime, formatErrors } from '../../lib/format';

const STATUS_OPTIONS = ['', 'todo', 'in_progress', 'in_review', 'completed', 'blocked'];
const PRIORITY_OPTIONS = ['', 'low', 'medium', 'high', 'critical'];

export default function TasksListPage() {
    const { isAdmin, isManager } = useAuth();
    const canCreate = isAdmin || isManager;

    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        status: '',
        priority: '',
        deadline_from: '',
        deadline_to: '',
    });
    const [page, setPage] = useState(1);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        const params = { page, per_page: 10 };
        if (filters.status) params.status = filters.status;
        if (filters.priority) params.priority = filters.priority;
        if (filters.deadline_from) params.deadline_from = filters.deadline_from;
        if (filters.deadline_to) params.deadline_to = filters.deadline_to;
        listTasks(params)
            .then((res) => mounted && setData(res))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [page, filters.status, filters.priority, filters.deadline_from, filters.deadline_to]);

    const onFilterChange = (key) => (e) => {
        setPage(1);
        setFilters((f) => ({ ...f, [key]: e.target.value }));
    };

    const tasks = data?.data ?? [];

    return (
        <div>
            <PageHeader
                title="Tasks"
                subtitle="All tasks you can view or work on"
                actions={
                    canCreate && (
                        <Link to="/tasks/new">
                            <Button>New task</Button>
                        </Link>
                    )
                }
            />

            <Card className="mb-4">
                <div className="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-4">
                    <Field label="Status" htmlFor="status">
                        <Select id="status" value={filters.status} onChange={onFilterChange('status')}>
                            {STATUS_OPTIONS.map((s) => (
                                <option key={s} value={s}>
                                    {s ? s.replace('_', ' ') : 'All statuses'}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Priority" htmlFor="priority">
                        <Select
                            id="priority"
                            value={filters.priority}
                            onChange={onFilterChange('priority')}
                        >
                            {PRIORITY_OPTIONS.map((p) => (
                                <option key={p} value={p}>
                                    {p || 'All priorities'}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Deadline from" htmlFor="deadline_from">
                        <TextInput
                            id="deadline_from"
                            type="date"
                            value={filters.deadline_from}
                            onChange={onFilterChange('deadline_from')}
                        />
                    </Field>
                    <Field label="Deadline to" htmlFor="deadline_to">
                        <TextInput
                            id="deadline_to"
                            type="date"
                            value={filters.deadline_to}
                            onChange={onFilterChange('deadline_to')}
                        />
                    </Field>
                </div>
            </Card>

            {loading && <div className="text-sm text-gray-500">Loading tasks…</div>}

            {!loading && tasks.length === 0 && (
                <EmptyState
                    title="No tasks found"
                    description={
                        canCreate
                            ? 'Create your first task to get started.'
                            : 'You have no tasks assigned yet.'
                    }
                    action={
                        canCreate && (
                            <Link to="/tasks/new">
                                <Button>Create task</Button>
                            </Link>
                        )
                    }
                />
            )}

            {!loading && tasks.length > 0 && (
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-5 py-3 text-left">Task</th>
                                    <th className="px-5 py-3 text-left">Project</th>
                                    <th className="px-5 py-3 text-left">Status</th>
                                    <th className="px-5 py-3 text-left">Priority</th>
                                    <th className="px-5 py-3 text-left">Assignee</th>
                                    <th className="px-5 py-3 text-left">Deadline</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {tasks.map((t) => (
                                    <tr key={t.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3">
                                            <Link
                                                to={`/tasks/${t.id}`}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                {t.name}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-3 text-gray-700">
                                            {t.project?.name ?? '—'}
                                        </td>
                                        <td className="px-5 py-3">
                                            <TaskStatusBadge status={t.status} />
                                        </td>
                                        <td className="px-5 py-3">
                                            <PriorityBadge priority={t.priority} />
                                        </td>
                                        <td className="px-5 py-3 text-gray-700">
                                            {t.assigned_to?.name ?? '—'}
                                        </td>
                                        <td className="px-5 py-3 text-gray-600">
                                            {formatDateTime(t.deadline)}
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
