import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import EmptyState from '../../components/ui/EmptyState';
import UserPicker from '../../components/ui/UserPicker';
import { useAuth } from '../../context/AuthContext';
import { listProjects } from '../../api/projects';
import { formatErrors } from '../../lib/format';

export default function ReportsIndexPage() {
    const { isAdmin } = useAuth();
    const [projects, setProjects] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        listProjects({ per_page: 100 })
            .then((res) => setProjects(res?.data ?? []))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => setLoading(false));
    }, []);

    return (
        <div>
            <PageHeader
                title="Reports"
                subtitle="Project completion and employee productivity"
            />

            <Card className="mb-6">
                <CardBody>
                    <h2 className="text-sm font-semibold text-gray-900">Employee report</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Enter an employee user ID to view their productivity report.
                    </p>
                    <EmployeeReportLookup />
                </CardBody>
            </Card>

            <h2 className="mb-3 text-lg font-semibold text-gray-900">Project reports</h2>
            {loading && <div className="text-sm text-gray-500">Loading projects…</div>}
            {!loading && projects.length === 0 && (
                <EmptyState
                    title="No projects available"
                    description={
                        isAdmin
                            ? 'Create a project to generate a report for it.'
                            : 'You have no projects assigned to manage.'
                    }
                />
            )}
            {!loading && projects.length > 0 && (
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-5 py-3 text-left">Project</th>
                                    <th className="px-5 py-3 text-left">Manager</th>
                                    <th className="px-5 py-3 text-right">Report</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {projects.map((p) => (
                                    <tr key={p.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 font-medium text-gray-900">
                                            {p.name}
                                        </td>
                                        <td className="px-5 py-3 text-gray-700">
                                            {p.assigned_manager?.name ?? '—'}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <Link to={`/reports/projects/${p.id}`}>
                                                <Button size="sm" variant="secondary">
                                                    View report
                                                </Button>
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}
        </div>
    );
}

function EmployeeReportLookup() {
    const [employeeId, setEmployeeId] = useState('');
    const navigate = useNavigate();
    return (
        <form
            className="mt-3 flex max-w-md gap-2"
            onSubmit={(e) => {
                e.preventDefault();
                if (employeeId) {
                    navigate(`/reports/employees/${employeeId}`);
                }
            }}
        >
            <div className="flex-1">
                <UserPicker
                    id="employee-picker"
                    role="employee"
                    value={employeeId}
                    onChange={(e) => setEmployeeId(e.target.value)}
                    blankLabel="— Select an employee —"
                />
            </div>
            <Button type="submit" disabled={!employeeId}>
                View
            </Button>
        </form>
    );
}
