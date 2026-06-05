import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import PageHeader from '../../components/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { getEmployeeReport } from '../../api/reports';
import { formatErrors } from '../../lib/format';

export default function EmployeeReportPage() {
    const { id } = useParams();
    const [report, setReport] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        getEmployeeReport(id)
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
                title={`Employee report: ${report.employee_name}`}
                actions={
                    <Link to="/reports">
                        <Button variant="secondary">Back</Button>
                    </Link>
                }
            />

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <Stat label="Assigned tasks" value={report.total_assigned_tasks} />
                <Stat label="Completed" value={report.completed_tasks} tone="green" />
                <Stat label="Pending" value={report.pending_tasks} />
                <Stat
                    label="Total hours logged"
                    value={`${report.total_hours_logged ?? 0} h`}
                />
                <Stat
                    label="Avg completion time"
                    value={`${report.avg_completion_time_days ?? 0} days`}
                />
                <Stat
                    label="Work logs submitted"
                    value={report.work_logs_submitted ?? 0}
                />
            </div>
        </div>
    );
}

function Stat({ label, value, tone = 'default' }) {
    const tones = {
        default: 'text-gray-900',
        green: 'text-green-700',
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
