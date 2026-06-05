import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { Field, Select, TextInput, Textarea } from '../../components/ui/Field';
import UserPicker from '../../components/ui/UserPicker';
import { listProjects } from '../../api/projects';
import { createTask, getTask, updateTask } from '../../api/tasks';
import { formatErrors } from '../../lib/format';

const EMPTY = {
    project_id: '',
    name: '',
    description: '',
    priority: 'medium',
    status: 'todo',
    deadline: '',
    assigned_to_id: '',
    estimated_hours: '',
};

const STATUSES = ['todo', 'in_progress', 'in_review', 'completed', 'blocked'];
const PRIORITIES = ['low', 'medium', 'high', 'critical'];

function toDateTimeLocal(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export default function TaskFormPage() {
    const { id } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const isEdit = Boolean(id);

    const [form, setForm] = useState({
        ...EMPTY,
        project_id: searchParams.get('project_id') ?? '',
    });
    const [projects, setProjects] = useState([]);
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(isEdit);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        listProjects({ per_page: 100 })
            .then((res) => setProjects(res?.data ?? []))
            .catch(() => setProjects([]));
    }, []);

    useEffect(() => {
        if (!isEdit) return;
        let mounted = true;
        getTask(id)
            .then((t) => {
                if (!mounted) return;
                setForm({
                    project_id: t.project_id ?? '',
                    name: t.name ?? '',
                    description: t.description ?? '',
                    priority: t.priority ?? 'medium',
                    status: t.status ?? 'todo',
                    deadline: toDateTimeLocal(t.deadline),
                    assigned_to_id: t.assigned_to_id ?? '',
                    estimated_hours: t.estimated_hours ?? '',
                });
            })
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [id, isEdit]);

    const onChange = (key) => (e) =>
        setForm((f) => ({ ...f, [key]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        const payload = {
            ...form,
            project_id: form.project_id ? Number(form.project_id) : null,
            assigned_to_id: form.assigned_to_id ? Number(form.assigned_to_id) : null,
            estimated_hours: form.estimated_hours === '' ? null : Number(form.estimated_hours),
        };
        try {
            const result = isEdit
                ? await updateTask(id, payload)
                : await createTask(payload);
            toast.success(isEdit ? 'Task updated' : 'Task created');
            navigate(`/tasks/${result.id}`, { replace: true });
        } catch (err) {
            const { message, fields } = formatErrors(err);
            setErrors(fields);
            toast.error(message);
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <div className="text-sm text-gray-500">Loading…</div>;

    return (
        <div>
            <PageHeader
                title={isEdit ? 'Edit task' : 'New task'}
                actions={
                    <Link to={isEdit ? `/tasks/${id}` : '/tasks'}>
                        <Button variant="secondary">Cancel</Button>
                    </Link>
                }
            />
            <Card>
                <CardBody>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Field label="Project" htmlFor="project_id" required error={errors.project_id}>
                            <Select
                                id="project_id"
                                value={form.project_id}
                                onChange={onChange('project_id')}
                                required
                                disabled={isEdit}
                            >
                                <option value="">Select a project…</option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Name" htmlFor="name" required error={errors.name}>
                            <TextInput
                                id="name"
                                value={form.name}
                                onChange={onChange('name')}
                                required
                                maxLength={255}
                            />
                        </Field>
                        <Field label="Description" htmlFor="description" error={errors.description}>
                            <Textarea
                                id="description"
                                value={form.description}
                                onChange={onChange('description')}
                                rows={4}
                            />
                        </Field>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field label="Status" htmlFor="status" required error={errors.status}>
                                <Select
                                    id="status"
                                    value={form.status}
                                    onChange={onChange('status')}
                                >
                                    {STATUSES.map((s) => (
                                        <option key={s} value={s}>
                                            {s.replace('_', ' ')}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field
                                label="Priority"
                                htmlFor="priority"
                                required
                                error={errors.priority}
                            >
                                <Select
                                    id="priority"
                                    value={form.priority}
                                    onChange={onChange('priority')}
                                >
                                    {PRIORITIES.map((p) => (
                                        <option key={p} value={p}>
                                            {p}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Deadline"
                                htmlFor="deadline"
                                required
                                error={errors.deadline}
                            >
                                <TextInput
                                    id="deadline"
                                    type="datetime-local"
                                    value={form.deadline}
                                    onChange={onChange('deadline')}
                                    required
                                />
                            </Field>
                            <Field
                                label="Estimated hours"
                                htmlFor="estimated_hours"
                                error={errors.estimated_hours}
                            >
                                <TextInput
                                    id="estimated_hours"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    value={form.estimated_hours}
                                    onChange={onChange('estimated_hours')}
                                />
                            </Field>
                        </div>
                        <Field
                            label="Assignee"
                            htmlFor="assigned_to_id"
                            error={errors.assigned_to_id}
                        >
                            <UserPicker
                                id="assigned_to_id"
                                role="employee"
                                value={form.assigned_to_id}
                                onChange={onChange('assigned_to_id')}
                                blankLabel="— Unassigned —"
                            />
                        </Field>
                        <div className="flex justify-end gap-2 pt-2">
                            <Link to={isEdit ? `/tasks/${id}` : '/tasks'}>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={submitting}>
                                {submitting ? 'Saving…' : isEdit ? 'Save changes' : 'Create task'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    );
}
