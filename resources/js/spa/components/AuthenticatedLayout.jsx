import { useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { RoleBadge } from './ui/Badge';

const NAV = [
    { to: '/dashboard', label: 'Dashboard', roles: ['admin', 'manager', 'employee'] },
    { to: '/projects', label: 'Projects', roles: ['admin', 'manager'] },
    { to: '/tasks', label: 'Tasks', roles: ['admin', 'manager', 'employee'] },
    { to: '/reports', label: 'Reports', roles: ['admin', 'manager'] },
    { to: '/users', label: 'Users', roles: ['admin'] },
    { to: '/activity-logs', label: 'Activity Log', roles: ['admin'] },
];

function navLinkClass({ isActive }) {
    return [
        'rounded-md px-3 py-2 text-sm font-medium transition',
        isActive
            ? 'bg-gray-900 text-white'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white',
    ].join(' ');
}

export default function AuthenticatedLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [menuOpen, setMenuOpen] = useState(false);

    const visibleNav = NAV.filter((item) => item.roles.includes(user?.role));

    const handleLogout = async () => {
        await logout();
        navigate('/login', { replace: true });
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="bg-gray-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-8">
                            <Link to="/dashboard" className="text-lg font-semibold text-white">
                                PM<span className="text-indigo-400">.demo</span>
                            </Link>
                            <div className="hidden gap-1 sm:flex">
                                {visibleNav.map((item) => (
                                    <NavLink
                                        key={item.to}
                                        to={item.to}
                                        className={navLinkClass}
                                    >
                                        {item.label}
                                    </NavLink>
                                ))}
                            </div>
                        </div>
                        <div className="hidden items-center gap-3 sm:flex">
                            <div className="text-right">
                                <div className="text-sm font-medium text-white">{user?.name}</div>
                                <div className="text-xs text-gray-400">{user?.email}</div>
                            </div>
                            <RoleBadge role={user?.role} />
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700"
                            >
                                Log out
                            </button>
                        </div>
                        <button
                            type="button"
                            onClick={() => setMenuOpen((s) => !s)}
                            className="rounded-md p-2 text-gray-300 hover:bg-gray-800 hover:text-white sm:hidden"
                            aria-label="Toggle navigation"
                        >
                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                {menuOpen && (
                    <div className="border-t border-gray-800 px-4 py-3 sm:hidden">
                        <div className="space-y-1">
                            {visibleNav.map((item) => (
                                <NavLink
                                    key={item.to}
                                    to={item.to}
                                    className={navLinkClass}
                                    onClick={() => setMenuOpen(false)}
                                >
                                    {item.label}
                                </NavLink>
                            ))}
                        </div>
                        <div className="mt-3 flex items-center justify-between border-t border-gray-800 pt-3">
                            <div>
                                <div className="text-sm font-medium text-white">{user?.name}</div>
                                <div className="text-xs text-gray-400">{user?.email}</div>
                            </div>
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700"
                            >
                                Log out
                            </button>
                        </div>
                    </div>
                )}
            </nav>

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Outlet />
            </main>
        </div>
    );
}
