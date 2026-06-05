import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import AuthenticatedLayout from './components/AuthenticatedLayout';
import LoginPage from './pages/LoginPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import DashboardPage from './pages/DashboardPage';
import ProjectsListPage from './pages/projects/ProjectsListPage';
import ProjectDetailPage from './pages/projects/ProjectDetailPage';
import ProjectFormPage from './pages/projects/ProjectFormPage';
import TasksListPage from './pages/tasks/TasksListPage';
import TaskDetailPage from './pages/tasks/TaskDetailPage';
import TaskFormPage from './pages/tasks/TaskFormPage';
import ReportsIndexPage from './pages/reports/ReportsIndexPage';
import ProjectReportPage from './pages/reports/ProjectReportPage';
import EmployeeReportPage from './pages/reports/EmployeeReportPage';
import ActivityLogPage from './pages/ActivityLogPage';
import UsersListPage from './pages/users/UsersListPage';
import UserFormPage from './pages/users/UserFormPage';
import PlaceholderPage from './pages/PlaceholderPage';
import NotFoundPage from './pages/NotFoundPage';

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <Toaster position="top-right" />
                <Routes>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/forgot-password" element={<ForgotPasswordPage />} />
                    <Route path="/reset-password/:token" element={<ResetPasswordPage />} />

                    <Route
                        element={
                            <ProtectedRoute>
                                <AuthenticatedLayout />
                            </ProtectedRoute>
                        }
                    >
                        <Route index element={<Navigate to="/dashboard" replace />} />
                        <Route path="/dashboard" element={<DashboardPage />} />

                        <Route
                            path="/projects"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <ProjectsListPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/projects/new"
                            element={
                                <ProtectedRoute roles={['admin']}>
                                    <ProjectFormPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/projects/:id"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <ProjectDetailPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/projects/:id/edit"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <ProjectFormPage />
                                </ProtectedRoute>
                            }
                        />

                        <Route path="/tasks" element={<TasksListPage />} />
                        <Route
                            path="/tasks/new"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <TaskFormPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route path="/tasks/:id" element={<TaskDetailPage />} />
                        <Route
                            path="/tasks/:id/edit"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <TaskFormPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/reports"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <ReportsIndexPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/reports/projects/:id"
                            element={
                                <ProtectedRoute roles={['admin', 'manager']}>
                                    <ProjectReportPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/reports/employees/:id"
                            element={
                                <ProtectedRoute roles={['admin', 'manager', 'employee']}>
                                    <EmployeeReportPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/activity-logs"
                            element={
                                <ProtectedRoute roles={['admin']}>
                                    <ActivityLogPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/users"
                            element={
                                <ProtectedRoute roles={['admin']}>
                                    <UsersListPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/users/new"
                            element={
                                <ProtectedRoute roles={['admin']}>
                                    <UserFormPage />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/users/:id/edit"
                            element={
                                <ProtectedRoute roles={['admin']}>
                                    <UserFormPage />
                                </ProtectedRoute>
                            }
                        />
                    </Route>

                    <Route path="*" element={<NotFoundPage />} />
                </Routes>
            </AuthProvider>
        </BrowserRouter>
    );
}
