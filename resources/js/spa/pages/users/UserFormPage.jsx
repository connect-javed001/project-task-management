import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { Field, Select, TextInput } from '../../components/ui/Field';
import { createUser, getUser, updateUser } from '../../api/users';
import { formatErrors } from '../../lib/format';

const EMPTY = {
    name: '',
    email: '',
    password: '',
    role: 'employee',
    status: 'active',
};

const ROLES = ['admin', 'manager', 'employee'];
const STATUSES = ['active', 'inactive'];

export default function UserFormPage() {
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
        getUser(id)
            .then((u) => {
                if (!mounted) return;
                setForm({
                    name: u.name ?? '',
                    email: u.email ?? '',
                    password: '',
                    role: u.role ?? 'employee',
                    status: u.status ?? 'active',
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
        const payload = { ...form };
        if (isEdit && !payload.password) delete payload.password;
        try {
            const result = isEdit
                ? await updateUser(id, payload)
                : await createUser(payload);
            toast.success(isEdit ? 'User updated' : 'User created');
            navigate(`/users`, { replace: true });
            return result;
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
                title={isEdit ? 'Edit user' : 'New user'}
                actions={
                    <Link to="/users">
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
                        <Field label="Email" htmlFor="email" required error={errors.email}>
                            <TextInput
                                id="email"
                                type="email"
                                value={form.email}
                                onChange={onChange('email')}
                                required
                            />
                        </Field>
                        <Field
                            label={isEdit ? 'New password (leave blank to keep current)' : 'Password'}
                            htmlFor="password"
                            required={!isEdit}
                            error={errors.password}
                            hint={isEdit ? undefined : 'At least 8 characters'}
                        >
                            <TextInput
                                id="password"
                                type="password"
                                value={form.password}
                                onChange={onChange('password')}
                                required={!isEdit}
                                minLength={8}
                                autoComplete="new-password"
                            />
                        </Field>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field label="Role" htmlFor="role" required error={errors.role}>
                                <Select id="role" value={form.role} onChange={onChange('role')}>
                                    {ROLES.map((r) => (
                                        <option key={r} value={r}>
                                            {r}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
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
                        </div>
                        <div className="flex justify-end gap-2 pt-2">
                            <Link to="/users">
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={submitting}>
                                {submitting ? 'Saving…' : isEdit ? 'Save changes' : 'Create user'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    );
}
