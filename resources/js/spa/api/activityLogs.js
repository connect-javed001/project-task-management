import api from './client';

export const listActivityLogs = (params = {}) =>
    api.get('/activity-logs', { params }).then((r) => r.data);

export const getActivityLog = (id) =>
    api.get(`/activity-logs/${id}`).then((r) => r.data);
