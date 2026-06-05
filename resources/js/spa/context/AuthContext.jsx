import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import * as authApi from '../api/auth';
import { setUnauthorizedHandler, tokenStore } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const clearSession = useCallback(() => {
        tokenStore.clear();
        setUser(null);
    }, []);

    useEffect(() => {
        setUnauthorizedHandler(clearSession);
    }, [clearSession]);

    useEffect(() => {
        const token = tokenStore.get();
        if (!token) {
            setLoading(false);
            return;
        }
        authApi
            .me()
            .then(setUser)
            .catch(() => tokenStore.clear())
            .finally(() => setLoading(false));
    }, []);

    const login = async (email, password) => {
        const { user: u, token } = await authApi.login(email, password);
        tokenStore.set(token);
        setUser(u);
        return u;
    };

    const logout = async () => {
        try {
            await authApi.logout();
        } catch {
            /* token may already be invalid */
        }
        clearSession();
    };

    const value = {
        user,
        loading,
        login,
        logout,
        isAdmin: user?.role === 'admin',
        isManager: user?.role === 'manager',
        isEmployee: user?.role === 'employee',
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}
