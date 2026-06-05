import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import EmptyState from '../../components/ui/EmptyState';
import { RoleBadge } from '../../components/ui/Badge';
import Badge from '../../components/ui/Badge';
import { Field, Select, TextInput } from '../../components/ui/Field';
import { deleteUser, listUsers } from '../../api/users';
import { formatErrors } from '../../lib/format';

const ROLE_OPTIONS = ['', 'admin', 'manager', 'employee'];
const STATUS_OPTIONS = ['', 'active', 'inactive'];

export default function UsersListPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ role: '', status: '', search: '' });
    const [page, setPage] = useState(1);
    const [refreshTick, setRefreshTick] = useState(0);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        const params = { page, per_page: 15 };
        if (filters.role) params.role = filters.role;
        if (filters.status) params.status = filters.status;
        if (filters.search) params.search = filters.search;
        listUsers(params)
            .then((res) => mounted && setData(res))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [page, filters.role, filters.status, filters.search, refreshTick]);

    const onFilterChange = (key) => (e) => {
        setPage(1);
        setFilters((f) => ({ ...f, [key]: e.target.value }));
    };

    const handleDelete = async (user) => {
        if (!confirm(`Delete user "${user.name}"? This cannot be undone.`)) return;
        try {
            await deleteUser(user.id);
            toast.success('User deleted');
            setRefreshTick((t) => t + 1);
        } catch (err) {
            toast.error(formatErrors(err).message);
        }
    };

    const users = data?.data ?? [];

    return (
        <div>
            <PageHeader
                title="Users"
                subtitle="Manage admins, project managers, and employees"
                actions={
                    <Link to="/users/new">
                        <Button>New user</Button>
                    </Link>
                }
            />

            <Card className="mb-4">
                <div className="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-3">
                    <Field label="Role" htmlFor="role">
                        <Select id="role" value={filters.role} onChange={onFilterChange('role')}>
                            {ROLE_OPTIONS.map((r) => (
                                <option key={r} value={r}>
                                    {r || 'All roles'}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Status" htmlFor="status">
                        <Select
                            id="status"
                            value={filters.status}
                            onChange={onFilterChange('status')}
                        >
                            {STATUS_OPTIONS.map((s) => (
                                <option key={s} value={s}>
                                    {s || 'All statuses'}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Search" htmlFor="search">
                        <TextInput
                            id="search"
                            placeholder="Name or email"
                            value={filters.search}
                            onChange={onFilterChange('search')}
                        />
                    </Field>
                </div>
            </Card>

            {loading && <div className="text-sm text-gray-500">Loading users…</div>}

            {!loading && users.length === 0 && (
                <EmptyState
                    title="No users found"
                    description="Try changing your filters or create a new user."
                    action={
                        <Link to="/users/new">
                            <Button>Create user</Button>
                        </Link>
                    }
                />
            )}

            {!loading && users.length > 0 && (
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-5 py-3 text-left">Name</th>
                                    <th className="px-5 py-3 text-left">Email</th>
                                    <th className="px-5 py-3 text-left">Role</th>
                                    <th className="px-5 py-3 text-left">Status</th>
                                    <th className="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {users.map((u) => (
                                    <tr key={u.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 font-medium text-gray-900">
                                            {u.name}
                                        </td>
                                        <td className="px-5 py-3 text-gray-600">{u.email}</td>
                                        <td className="px-5 py-3">
                                            <RoleBadge role={u.role} />
                                        </td>
                                        <td className="px-5 py-3">
                                            <Badge color={u.status === 'active' ? 'green' : 'gray'}>
                                                {u.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link to={`/users/${u.id}/edit`}>
                                                    <Button size="sm" variant="secondary">
                                                        Edit
                                                    </Button>
                                                </Link>
                                                <Button
                                                    size="sm"
                                                    variant="danger"
                                                    onClick={() => handleDelete(u)}
                                                >
                                                    Delete
                                                </Button>
                                            </div>
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
