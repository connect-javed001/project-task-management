import { useEffect, useRef, useState } from 'react';
import toast from 'react-hot-toast';
import Card, { CardBody } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { Field, TextInput, Textarea } from '../../components/ui/Field';
import EmptyState from '../../components/ui/EmptyState';
import { useAuth } from '../../context/AuthContext';
import {
    createWorkLog,
    createWorkLogComment,
    deleteWorkLog,
    deleteWorkLogComment,
    listWorkLogComments,
    listWorkLogs,
    updateWorkLog,
    updateWorkLogComment,
} from '../../api/workLogs';
import { formatDateTime, formatErrors } from '../../lib/format';

export default function WorkLogsSection({ task }) {
    const { user, isAdmin, isManager, isEmployee } = useAuth();
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);

    const managesProject =
        isManager &&
        task.project &&
        (task.project.assigned_manager_id === user?.id ||
            task.project.created_by_id === user?.id);
    const canSubmitLog = isEmployee && task.assigned_to_id === user?.id;
    const canComment = isAdmin || managesProject;

    const loadLogs = async () => {
        try {
            const res = await listWorkLogs(task.id, { per_page: 50 });
            setLogs(res?.data ?? []);
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadLogs();
    }, [task.id]);

    const onLogCreated = (log) => setLogs((prev) => [log, ...prev]);
    const onLogUpdated = (log) =>
        setLogs((prev) => prev.map((l) => (l.id === log.id ? { ...l, ...log } : l)));
    const onLogDeleted = (logId) => setLogs((prev) => prev.filter((l) => l.id !== logId));

    return (
        <div>
            <h2 className="mb-3 text-lg font-semibold text-gray-900">Work logs</h2>

            {canSubmitLog && <NewWorkLogForm taskId={task.id} onCreated={onLogCreated} />}

            {loading && <div className="text-sm text-gray-500">Loading work logs…</div>}

            {!loading && logs.length === 0 && (
                <EmptyState
                    title="No work logs yet"
                    description={
                        canSubmitLog
                            ? 'Submit your first log above.'
                            : 'The assignee has not submitted any work logs yet.'
                    }
                />
            )}

            <div className="space-y-3">
                {logs.map((log) => (
                    <WorkLogItem
                        key={log.id}
                        taskId={task.id}
                        log={log}
                        canEdit={log.employee_id === user?.id}
                        canComment={canComment}
                        onUpdated={onLogUpdated}
                        onDeleted={onLogDeleted}
                    />
                ))}
            </div>
        </div>
    );
}

function NewWorkLogForm({ taskId, onCreated }) {
    const [description, setDescription] = useState('');
    const [hours, setHours] = useState('');
    const [attachment, setAttachment] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState({});
    const fileRef = useRef(null);

    const reset = () => {
        setDescription('');
        setHours('');
        setAttachment(null);
        setErrors({});
        if (fileRef.current) fileRef.current.value = '';
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            const created = await createWorkLog(taskId, {
                description,
                hours_worked: hours,
                attachment,
            });
            toast.success('Work log submitted');
            onCreated(created);
            reset();
        } catch (err) {
            const { message, fields } = formatErrors(err);
            setErrors(fields);
            toast.error(message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Card className="mb-4">
            <CardBody>
                <form onSubmit={handleSubmit} className="space-y-3">
                    <Field
                        label="What did you work on?"
                        htmlFor="wl-description"
                        required
                        error={errors.description}
                    >
                        <Textarea
                            id="wl-description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            rows={3}
                            required
                        />
                    </Field>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <Field
                            label="Hours worked"
                            htmlFor="wl-hours"
                            required
                            error={errors.hours_worked}
                        >
                            <TextInput
                                id="wl-hours"
                                type="number"
                                step="0.5"
                                min="0.5"
                                max="24"
                                value={hours}
                                onChange={(e) => setHours(e.target.value)}
                                required
                            />
                        </Field>
                        <Field
                            label="Attachment (optional)"
                            htmlFor="wl-attachment"
                            hint="Max 5 MB"
                            error={errors.attachment}
                        >
                            <input
                                id="wl-attachment"
                                ref={fileRef}
                                type="file"
                                onChange={(e) => setAttachment(e.target.files?.[0] ?? null)}
                                className="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                        </Field>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={submitting}>
                            {submitting ? 'Submitting…' : 'Submit work log'}
                        </Button>
                    </div>
                </form>
            </CardBody>
        </Card>
    );
}

function WorkLogItem({ taskId, log, canEdit, canComment, onUpdated, onDeleted }) {
    const [editing, setEditing] = useState(false);
    const [description, setDescription] = useState(log.description);
    const [hours, setHours] = useState(log.hours_worked);
    const [saving, setSaving] = useState(false);

    const saveEdits = async () => {
        setSaving(true);
        try {
            const updated = await updateWorkLog(taskId, log.id, {
                description,
                hours_worked: hours,
            });
            onUpdated(updated);
            setEditing(false);
            toast.success('Work log updated');
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setSaving(false);
        }
    };

    const removeLog = async () => {
        if (!confirm('Delete this work log?')) return;
        try {
            await deleteWorkLog(taskId, log.id);
            onDeleted(log.id);
            toast.success('Work log deleted');
        } catch (err) {
            toast.error(formatErrors(err).message);
        }
    };

    return (
        <Card>
            <CardBody>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {log.employee?.name ?? 'Employee'}
                        </div>
                        <div className="text-xs text-gray-500">
                            {formatDateTime(log.created_at)} · {log.hours_worked} h
                        </div>
                    </div>
                    {canEdit && !editing && (
                        <div className="flex gap-2">
                            <Button size="sm" variant="secondary" onClick={() => setEditing(true)}>
                                Edit
                            </Button>
                            <Button size="sm" variant="danger" onClick={removeLog}>
                                Delete
                            </Button>
                        </div>
                    )}
                </div>

                {editing ? (
                    <div className="mt-3 space-y-3">
                        <Textarea
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            rows={3}
                        />
                        <TextInput
                            type="number"
                            step="0.5"
                            min="0.5"
                            max="24"
                            value={hours}
                            onChange={(e) => setHours(e.target.value)}
                        />
                        <div className="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                type="button"
                                onClick={() => {
                                    setDescription(log.description);
                                    setHours(log.hours_worked);
                                    setEditing(false);
                                }}
                            >
                                Cancel
                            </Button>
                            <Button size="sm" disabled={saving} onClick={saveEdits}>
                                {saving ? 'Saving…' : 'Save'}
                            </Button>
                        </div>
                    </div>
                ) : (
                    <p className="mt-3 whitespace-pre-wrap text-sm text-gray-700">
                        {log.description}
                    </p>
                )}

                {log.attachment_path && (
                    <div className="mt-2 text-xs text-gray-500">
                        Attachment: <span className="font-mono">{log.attachment_path}</span>
                    </div>
                )}

                <CommentsThread
                    workLogId={log.id}
                    initial={log.comments ?? []}
                    canComment={canComment}
                />
            </CardBody>
        </Card>
    );
}

function CommentsThread({ workLogId, initial, canComment }) {
    const { user } = useAuth();
    const [comments, setComments] = useState(initial);
    const [text, setText] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [refreshed, setRefreshed] = useState(false);

    useEffect(() => {
        if (refreshed) return;
        listWorkLogComments(workLogId)
            .then(setComments)
            .catch(() => {})
            .finally(() => setRefreshed(true));
    }, [workLogId, refreshed]);

    const addComment = async (e) => {
        e.preventDefault();
        if (!text.trim()) return;
        setSubmitting(true);
        try {
            const created = await createWorkLogComment(workLogId, text);
            setComments((prev) => [...prev, created]);
            setText('');
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setSubmitting(false);
        }
    };

    const removeComment = async (id) => {
        if (!confirm('Delete this comment?')) return;
        try {
            await deleteWorkLogComment(workLogId, id);
            setComments((prev) => prev.filter((c) => c.id !== id));
        } catch (err) {
            toast.error(formatErrors(err).message);
        }
    };

    return (
        <div className="mt-4 border-t border-gray-100 pt-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Replies
            </div>
            <div className="mt-2 space-y-2">
                {comments.length === 0 && (
                    <p className="text-xs text-gray-400">No replies yet.</p>
                )}
                {comments.map((c) => (
                    <CommentItem
                        key={c.id}
                        workLogId={workLogId}
                        comment={c}
                        canEdit={c.user_id === user?.id}
                        onUpdated={(updated) =>
                            setComments((prev) =>
                                prev.map((x) => (x.id === updated.id ? updated : x)),
                            )
                        }
                        onDeleted={() => removeComment(c.id)}
                    />
                ))}
            </div>
            {canComment && (
                <form onSubmit={addComment} className="mt-3 flex gap-2">
                    <TextInput
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        placeholder="Write a reply…"
                        maxLength={1000}
                    />
                    <Button type="submit" size="sm" disabled={submitting || !text.trim()}>
                        {submitting ? 'Sending…' : 'Reply'}
                    </Button>
                </form>
            )}
        </div>
    );
}

function CommentItem({ workLogId, comment, canEdit, onUpdated, onDeleted }) {
    const [editing, setEditing] = useState(false);
    const [text, setText] = useState(comment.comment);
    const [saving, setSaving] = useState(false);

    const save = async () => {
        setSaving(true);
        try {
            const updated = await updateWorkLogComment(workLogId, comment.id, text);
            onUpdated(updated);
            setEditing(false);
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="rounded-md bg-gray-50 px-3 py-2">
            <div className="flex items-center justify-between text-xs text-gray-500">
                <span>
                    <span className="font-medium text-gray-700">
                        {comment.user?.name ?? 'User'}
                    </span>{' '}
                    · {formatDateTime(comment.created_at)}
                </span>
                {canEdit && !editing && (
                    <span className="flex gap-2">
                        <button
                            type="button"
                            className="text-indigo-600 hover:text-indigo-800"
                            onClick={() => setEditing(true)}
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            className="text-red-600 hover:text-red-800"
                            onClick={onDeleted}
                        >
                            Delete
                        </button>
                    </span>
                )}
            </div>
            {editing ? (
                <div className="mt-2 flex gap-2">
                    <TextInput
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        maxLength={1000}
                    />
                    <Button size="sm" variant="secondary" onClick={() => setEditing(false)}>
                        Cancel
                    </Button>
                    <Button size="sm" disabled={saving} onClick={save}>
                        {saving ? 'Saving…' : 'Save'}
                    </Button>
                </div>
            ) : (
                <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800">{comment.comment}</p>
            )}
        </div>
    );
}
