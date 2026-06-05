import api from './client';

export const listWorkLogs = (taskId, params = {}) =>
    api.get(`/tasks/${taskId}/work-logs`, { params }).then((r) => r.data);

export const createWorkLog = (taskId, payload) => {
    const form = new FormData();
    form.append('description', payload.description);
    form.append('hours_worked', payload.hours_worked);
    if (payload.attachment) form.append('attachment', payload.attachment);
    return api
        .post(`/tasks/${taskId}/work-logs`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        .then((r) => r.data);
};

export const updateWorkLog = (taskId, workLogId, payload) =>
    api.put(`/tasks/${taskId}/work-logs/${workLogId}`, payload).then((r) => r.data);

export const deleteWorkLog = (taskId, workLogId) =>
    api.delete(`/tasks/${taskId}/work-logs/${workLogId}`).then((r) => r.data);

export const listWorkLogComments = (workLogId) =>
    api.get(`/work-logs/${workLogId}/comments`).then((r) => r.data);

export const createWorkLogComment = (workLogId, comment) =>
    api.post(`/work-logs/${workLogId}/comments`, { comment }).then((r) => r.data);

export const updateWorkLogComment = (workLogId, commentId, comment) =>
    api.put(`/work-logs/${workLogId}/comments/${commentId}`, { comment }).then((r) => r.data);

export const deleteWorkLogComment = (workLogId, commentId) =>
    api.delete(`/work-logs/${workLogId}/comments/${commentId}`).then((r) => r.data);
