import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { Field, Select, TextInput, Textarea } from '../../components/ui/Field';
import UserPicker from '../../components/ui/UserPicker';
import { createProject, getProject, updateProject } from '../../api/projects';
import { formatErrors } from '../../lib/format';

const EMPTY = {
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'planning',
    assigned_manager_id: '',
};

const STATUSES = ['planning', 'active', 'completed', 'archived'];

function toDateInput(value) {
    if (!value) return '';
    return String(value).slice(0, 10);
}

export default function ProjectFormPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const isEdit = Boolean(id);

    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(isEdit);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!isEdit) return;
        let mounted = true;
        getProject(id)
            .then((p) => {
                if (!mounted) return;
                setForm({
                    name: p.name ?? '',
                    description: p.description ?? '',
                    start_date: toDateInput(p.start_date),
                    end_date: toDateInput(p.end_date),
                    status: p.status ?? 'planning',
                    assigned_manager_id: p.assigned_manager_id ?? '',
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
            assigned_manager_id: form.assigned_manager_id || null,
        };
        try {
            const result = isEdit
                ? await updateProject(id, payload)
                : await createProject(payload);
            toast.success(isEdit ? 'Project updated' : 'Project created');
            navigate(`/projects/${result.id}`, { replace: true });
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
                title={isEdit ? 'Edit project' : 'New project'}
                actions={
                    <Link to={isEdit ? `/projects/${id}` : '/projects'}>
                        <Button variant="secondary">Cancel</Button>
                    </Link>
                }
            />
            <Card>
                <CardBody>
                    <form onSubmit={handleSubmit} className="space-y-4">
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
                            <Field
                                label="Start date"
                                htmlFor="start_date"
                                required
                                error={errors.start_date}
                            >
                                <TextInput
                                    id="start_date"
                                    type="date"
                                    value={form.start_date}
                                    onChange={onChange('start_date')}
                                    required
                                />
                            </Field>
                            <Field
                                label="End date"
                                htmlFor="end_date"
                                required
                                error={errors.end_date}
                            >
                                <TextInput
                                    id="end_date"
                                    type="date"
                                    value={form.end_date}
                                    onChange={onChange('end_date')}
                                    required
                                />
                            </Field>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field label="Status" htmlFor="status" required error={errors.status}>
                                <Select
                                    id="status"
                                    value={form.status}
                                    onChange={onChange('status')}
                                >
                                    {STATUSES.map((s) => (
                                        <option key={s} value={s}>
                                            {s}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field
                                label="Manager"
                                htmlFor="assigned_manager_id"
                                error={errors.assigned_manager_id}
                            >
                                <UserPicker
                                    id="assigned_manager_id"
                                    role="manager"
                                    value={form.assigned_manager_id}
                                    onChange={onChange('assigned_manager_id')}
                                    blankLabel="— No manager —"
                                />
                            </Field>
                        </div>
                        <div className="flex justify-end gap-2 pt-2">
                            <Link to={isEdit ? `/projects/${id}` : '/projects'}>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={submitting}>
                                {submitting
                                    ? 'Saving…'
                                    : isEdit
                                      ? 'Save changes'
                                      : 'Create project'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    );
}
