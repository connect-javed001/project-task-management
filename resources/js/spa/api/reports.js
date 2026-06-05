import api from './client';

export const getDashboardStats = () =>
    api.get('/dashboard/stats').then((r) => r.data);

export const getProjectReport = (id) =>
    api.get(`/reports/projects/${id}`).then((r) => r.data);

export const getEmployeeReport = (id) =>
    api.get(`/reports/employees/${id}`).then((r) => r.data);
