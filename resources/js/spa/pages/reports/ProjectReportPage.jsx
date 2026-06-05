import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { PriorityBadge, TaskStatusBadge } from '../../components/ui/Badge';
import { getProjectReport } from '../../api/reports';
import { formatDateTime, formatErrors } from '../../lib/format';

export default function ProjectReportPage() {
    const { id } = useParams();
    const [report, setReport] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        getProjectReport(id)
            .then((r) => mounted && setReport(r))
            .catch((err) => toast.error(formatErrors(err).message))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [id]);

    if (loading) return <div className="text-sm text-gray-500">Loading report…</div>;
    if (!report) return <div className="text-sm text-gray-500">Report not available.</div>;

    return (
        <div>
            <PageHeader
                title={`Project report: ${report.project_name}`}
                actions={
                    <Link to="/reports">
                        <Button variant="secondary">Back</Button>
                    </Link>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <Stat label="Completion" value={`${report.completion_percentage}%`} />
                <Stat label="Total tasks" value={report.total_tasks} />
                <Stat label="Completed" value={report.completed_tasks} tone="green" />
                <Stat label="Pending" value={report.pending_tasks} />
                <Stat label="Overdue" value={report.overdue_tasks} tone="red" />
            </div>

            <h2 className="mb-3 text-lg font-semibold text-gray-900">Tasks</h2>
            <Card>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th className="px-5 py-3 text-left">Name</th>
                                <th className="px-5 py-3 text-left">Status</th>
                                <th className="px-5 py-3 text-left">Priority</th>
                                <th className="px-5 py-3 text-left">Assignee</th>
                                <th className="px-5 py-3 text-left">Deadline</th>
                                <th className="px-5 py-3 text-right">Hours logged</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {(report.tasks ?? []).map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50">
                                    <td className="px-5 py-3 font-medium text-gray-900">
                                        <Link
                                            to={`/tasks/${t.id}`}
                                            className="text-indigo-600 hover:text-indigo-800"
                                        >
                                            {t.name}
                                        </Link>
                                    </td>
                                    <td className="px-5 py-3">
                                        <TaskStatusBadge status={t.status} />
                                    </td>
                                    <td className="px-5 py-3">
                                        <PriorityBadge priority={t.priority} />
                                    </td>
                                    <td className="px-5 py-3 text-gray-700">
                                        {t.assigned_to ?? '—'}
                                    </td>
                                    <td className="px-5 py-3 text-gray-600">
                                        {formatDateTime(t.deadline)}
                                    </td>
                                    <td className="px-5 py-3 text-right text-gray-700">
                                        {t.hours_logged ?? 0}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    );
}

function Stat({ label, value, tone = 'default' }) {
    const tones = {
        default: 'text-gray-900',
        green: 'text-green-700',
        red: 'text-red-700',
    };
    return (
        <Card>
            <CardBody>
                <div className="text-xs font-medium uppercase tracking-wide text-gray-500">
                    {label}
                </div>
                <div className={`mt-1 text-2xl font-semibold ${tones[tone]}`}>{value}</div>
            </CardBody>
        </Card>
    );
}
