import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { PriorityBadge, TaskStatusBadge } from '../../components/ui/Badge';
import { Select } from '../../components/ui/Field';
import { useAuth } from '../../context/AuthContext';
import {
    deleteTask,
    getTask,
    updateTaskStatus,
} from '../../api/tasks';
import { formatDateTime, formatErrors } from '../../lib/format';
import WorkLogsSection from './WorkLogsSection';

const STATUSES = ['todo', 'in_progress', 'in_review', 'completed', 'blocked'];

export default function TaskDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user, isAdmin, isManager, isEmployee } = useAuth();

    const [task, setTask] = useState(null);
    const [loading, setLoading] = useState(true);
    const [savingStatus, setSavingStatus] = useState(false);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        getTask(id)
            .then((t) => mounted && setTask(t))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [id]);

    const managesProject = useMemo(() => {
        if (!task || !user) return false;
        const proj = task.project;
        return (
            isManager &&
            proj &&
            (proj.assigned_manager_id === user.id || proj.created_by_id === user.id)
        );
    }, [task, user, isManager]);

    const canEdit = isAdmin || managesProject;
    const canDelete = isAdmin || managesProject;
    const canUpdateStatus =
        isAdmin || managesProject || (isEmployee && task?.assigned_to_id === user?.id);

    const handleStatusChange = async (newStatus) => {
        if (!task || newStatus === task.status) return;
        setSavingStatus(true);
        try {
            const updated = await updateTaskStatus(task.id, newStatus);
            setTask((t) => ({ ...t, ...updated }));
            toast.success('Status updated');
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setSavingStatus(false);
        }
    };

    const handleDelete = async () => {
        if (!confirm(`Delete task "${task.name}"? This cannot be undone.`)) return;
        setDeleting(true);
        try {
            await deleteTask(task.id);
            toast.success('Task deleted');
            navigate('/tasks', { replace: true });
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setDeleting(false);
        }
    };

    if (loading) return <div className="text-sm text-gray-500">Loading task…</div>;
    if (!task) return <div className="text-sm text-gray-500">Task not found.</div>;

    return (
        <div>
            <PageHeader
                title={task.name}
                subtitle={
                    <span className="flex flex-wrap items-center gap-2">
                        <TaskStatusBadge status={task.status} />
                        <PriorityBadge priority={task.priority} />
                        <span className="text-gray-500">
                            Deadline: {formatDateTime(task.deadline)}
                        </span>
                    </span>
                }
                actions={
                    <>
                        <Link to="/tasks">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        {canEdit && (
                            <Link to={`/tasks/${task.id}/edit`}>
                                <Button>Edit</Button>
                            </Link>
                        )}
                        {canDelete && (
                            <Button variant="danger" disabled={deleting} onClick={handleDelete}>
                                {deleting ? 'Deleting…' : 'Delete'}
                            </Button>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardBody>
                        <h2 className="text-sm font-semibold text-gray-900">Description</h2>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-gray-700">
                            {task.description || 'No description provided.'}
                        </p>
                    </CardBody>
                </Card>
                <Card>
                    <CardBody className="space-y-3 text-sm">
                        <Detail
                            label="Project"
                            value={
                                task.project ? (
                                    <Link
                                        to={`/projects/${task.project.id}`}
                                        className="text-indigo-600 hover:text-indigo-800"
                                    >
                                        {task.project.name}
                                    </Link>
                                ) : (
                                    '—'
                                )
                            }
                        />
                        <Detail label="Assignee" value={task.assigned_to?.name ?? '—'} />
                        <Detail label="Created by" value={task.creator?.name ?? '—'} />
                        <Detail
                            label="Estimated hours"
                            value={task.estimated_hours ?? '—'}
                        />
                        <Detail label="Created" value={formatDateTime(task.created_at)} />
                        <Detail label="Updated" value={formatDateTime(task.updated_at)} />
                        {canUpdateStatus && (
                            <div className="border-t border-gray-100 pt-3">
                                <label
                                    htmlFor="status-updater"
                                    className="block text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Update status
                                </label>
                                <Select
                                    id="status-updater"
                                    className="mt-1"
                                    value={task.status}
                                    disabled={savingStatus}
                                    onChange={(e) => handleStatusChange(e.target.value)}
                                >
                                    {STATUSES.map((s) => (
                                        <option key={s} value={s}>
                                            {s.replace('_', ' ')}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                        )}
                    </CardBody>
                </Card>
            </div>

            <div className="mt-6">
                <WorkLogsSection task={task} />
            </div>
        </div>
    );
}

function Detail({ label, value }) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <span className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </span>
            <span className="text-right text-gray-900">{value}</span>
        </div>
    );
}
