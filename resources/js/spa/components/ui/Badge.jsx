const palette = {
    gray: 'bg-gray-100 text-gray-700 ring-gray-200',
    green: 'bg-green-100 text-green-700 ring-green-200',
    yellow: 'bg-yellow-100 text-yellow-800 ring-yellow-200',
    red: 'bg-red-100 text-red-700 ring-red-200',
    blue: 'bg-blue-100 text-blue-700 ring-blue-200',
    indigo: 'bg-indigo-100 text-indigo-700 ring-indigo-200',
    purple: 'bg-purple-100 text-purple-700 ring-purple-200',
};

const projectStatusColor = {
    planning: 'blue',
    active: 'green',
    completed: 'purple',
    archived: 'gray',
};

const taskStatusColor = {
    todo: 'gray',
    in_progress: 'blue',
    in_review: 'yellow',
    completed: 'green',
    blocked: 'red',
};

const priorityColor = {
    low: 'gray',
    medium: 'blue',
    high: 'yellow',
    critical: 'red',
};

export default function Badge({ color = 'gray', children, className = '' }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${palette[color] ?? palette.gray} ${className}`}
        >
            {children}
        </span>
    );
}

export function ProjectStatusBadge({ status }) {
    return (
        <Badge color={projectStatusColor[status] ?? 'gray'}>{status?.replace('_', ' ')}</Badge>
    );
}

export function TaskStatusBadge({ status }) {
    return <Badge color={taskStatusColor[status] ?? 'gray'}>{status?.replace('_', ' ')}</Badge>;
}

export function PriorityBadge({ priority }) {
    return <Badge color={priorityColor[priority] ?? 'gray'}>{priority}</Badge>;
}

export function RoleBadge({ role }) {
    const color = role === 'admin' ? 'purple' : role === 'manager' ? 'indigo' : 'gray';
    return <Badge color={color}>{role}</Badge>;
}
