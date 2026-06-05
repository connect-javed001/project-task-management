import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import EmptyState from '../../components/ui/EmptyState';
import {
    PriorityBadge,
    ProjectStatusBadge,
    TaskStatusBadge,
} from '../../components/ui/Badge';
import { useAuth } from '../../context/AuthContext';
import { deleteProject, getProject } from '../../api/projects';
import { formatDate, formatDateTime, formatErrors } from '../../lib/format';

export default function ProjectDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user, isAdmin } = useAuth();
    const [project, setProject] = useState(null);
    const [loading, setLoading] = useState(true);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        getProject(id)
            .then((p) => mounted && setProject(p))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [id]);

    const canEdit =
        project &&
        user &&
        (isAdmin || project.assigned_manager_id === user.id);
    const canDelete = isAdmin;

    const handleDelete = async () => {
        if (!confirm(`Delete project "${project.name}"? This cannot be undone.`)) return;
        setDeleting(true);
        try {
            await deleteProject(project.id);
            toast.success('Project deleted');
            navigate('/projects', { replace: true });
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setDeleting(false);
        }
    };

    if (loading) return <div className="text-sm text-gray-500">Loading project…</div>;
    if (!project) return <div className="text-sm text-gray-500">Project not found.</div>;

    const tasks = project.tasks ?? [];

    return (
        <div>
            <PageHeader
                title={project.name}
                subtitle={
                    <span className="flex items-center gap-2">
                        <ProjectStatusBadge status={project.status} />
                        <span className="text-gray-500">
                            {formatDate(project.start_date)} – {formatDate(project.end_date)}
                        </span>
                    </span>
                }
                actions={
                    <>
                        <Link to="/projects">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        {canEdit && (
                            <Link to={`/projects/${project.id}/edit`}>
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
                            {project.description || 'No description provided.'}
                        </p>
                    </CardBody>
                </Card>
                <Card>
                    <CardBody className="space-y-3 text-sm">
                        <Detail label="Manager" value={project.assigned_manager?.name ?? '—'} />
                        <Detail label="Created by" value={project.creator?.name ?? '—'} />
                        <Detail label="Created" value={formatDateTime(project.created_at)} />
                        <Detail label="Updated" value={formatDateTime(project.updated_at)} />
                    </CardBody>
                </Card>
            </div>

            <div className="mt-6">
                <h2 className="mb-3 text-lg font-semibold text-gray-900">
                    Tasks <span className="text-sm font-normal text-gray-500">({tasks.length})</span>
                </h2>
                {tasks.length === 0 ? (
                    <EmptyState
                        title="No tasks yet"
                        description="Tasks will appear here once they are created."
                    />
                ) : (
                    <Card>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th className="px-5 py-3 text-left">Name</th>
                                        <th className="px-5 py-3 text-left">Status</th>
                                        <th className="px-5 py-3 text-left">Priority</th>
                                        <th className="px-5 py-3 text-left">Deadline</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {tasks.map((t) => (
                                        <tr key={t.id} className="hover:bg-gray-50">
                                            <td className="px-5 py-3 font-medium text-gray-900">
                                                {t.name}
                                            </td>
                                            <td className="px-5 py-3">
                                                <TaskStatusBadge status={t.status} />
                                            </td>
                                            <td className="px-5 py-3">
                                                <PriorityBadge priority={t.priority} />
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
