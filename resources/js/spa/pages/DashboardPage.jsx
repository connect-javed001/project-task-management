import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import PageHeader from '../components/PageHeader';
import Card, { CardBody } from '../components/ui/Card';
import { TaskStatusBadge } from '../components/ui/Badge';
import { useAuth } from '../context/AuthContext';
import { getDashboardStats } from '../api/reports';
import { formatErrors } from '../lib/format';

const STAT_LABELS = {
    total_projects: 'Total Projects',
    total_tasks: 'Total Tasks',
    active_employees: 'Active Employees',
    overdue_tasks: 'Overdue Tasks',
    completed_tasks: 'Completed Tasks',
    total_hours_logged: 'Hours Logged',
    project_completion_avg: 'Avg. Completion %',
    managed_projects: 'Managed Projects',
    active_tasks: 'Active Tasks',
    upcoming_deadlines: 'Upcoming Deadlines',
    assigned_tasks: 'Assigned Tasks',
    tasks_in_progress: 'Tasks In Progress',
    tasks_due_soon: 'Due Soon',
};

function StatCard({ label, value }) {
    const display =
        typeof value === 'number' ? Number.isInteger(value) ? value : value.toFixed(1) : value;
    return (
        <Card>
            <CardBody>
                <div className="text-xs font-medium uppercase tracking-wide text-gray-500">
                    {label}
                </div>
                <div className="mt-1 text-2xl font-semibold text-gray-900">{display}</div>
            </CardBody>
        </Card>
    );
}

export default function DashboardPage() {
    const { user } = useAuth();
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        getDashboardStats()
            .then((data) => mounted && setStats(data))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, []);

    const numericEntries = stats
        ? Object.entries(stats).filter(
              ([k, v]) => typeof v === 'number' && k !== 'employee_productivity',
          )
        : [];

    const productivity = stats?.employee_productivity ?? [];
    const recentLogs = stats?.recent_work_logs ?? [];

    return (
        <div>
            <PageHeader
                title={`Welcome back, ${user?.name?.split(' ')[0] ?? ''}`}
                subtitle="Here's an overview based on your role and assignments."
            />

            {loading && <div className="text-sm text-gray-500">Loading stats…</div>}

            {!loading && stats && (
                <>
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                        {numericEntries.map(([key, value]) => (
                            <StatCard
                                key={key}
                                label={STAT_LABELS[key] ?? key.replace(/_/g, ' ')}
                                value={value}
                            />
                        ))}
                    </div>

                    {productivity.length > 0 && (
                        <Card className="mt-6">
                            <div className="border-b border-gray-200 px-5 py-4">
                                <h3 className="text-base font-semibold text-gray-900">
                                    Employee productivity
                                </h3>
                            </div>
                            <div className="divide-y divide-gray-100">
                                {productivity.map((row) => (
                                    <div
                                        key={row.employee_id}
                                        className="flex items-center justify-between px-5 py-3 text-sm"
                                    >
                                        <span className="font-medium text-gray-900">
                                            {row.employee_name ?? `User #${row.employee_id}`}
                                        </span>
                                        <span className="text-gray-600">
                                            {row.completed_tasks}/{row.assigned_tasks} completed
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}

                    {recentLogs.length > 0 && (
                        <Card className="mt-6">
                            <div className="border-b border-gray-200 px-5 py-4">
                                <h3 className="text-base font-semibold text-gray-900">
                                    Your recent work logs
                                </h3>
                            </div>
                            <div className="divide-y divide-gray-100">
                                {recentLogs.map((log) => (
                                    <div key={log.id} className="px-5 py-3 text-sm">
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium text-gray-900">
                                                {log.hours_worked}h
                                            </span>
                                            <TaskStatusBadge status={log.task?.status ?? 'todo'} />
                                        </div>
                                        <p className="mt-1 text-gray-600 line-clamp-2">
                                            {log.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}
                </>
            )}
        </div>
    );
}
