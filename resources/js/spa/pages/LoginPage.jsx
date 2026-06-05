import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../context/AuthContext';
import Button from '../components/ui/Button';
import { Field, TextInput } from '../components/ui/Field';
import { formatErrors } from '../lib/format';

export default function LoginPage() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState({});

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            await login(email, password);
            const dest = location.state?.from?.pathname ?? '/dashboard';
            navigate(dest, { replace: true });
        } catch (err) {
            const { message, fields } = formatErrors(err);
            setErrors(fields);
            toast.error(fields.email || message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 to-indigo-50 px-4">
            <div className="w-full max-w-md">
                <div className="mb-6 text-center">
                    <h1 className="text-2xl font-bold text-gray-900">
                        PM<span className="text-indigo-600">.demo</span>
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Sign in to manage projects, tasks, and work logs
                    </p>
                </div>
                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-lg bg-white p-6 shadow ring-1 ring-gray-200"
                >
                    <Field label="Email" htmlFor="email" required error={errors.email}>
                        <TextInput
                            id="email"
                            type="email"
                            value={email}
                            autoComplete="email"
                            autoFocus
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />
                    </Field>
                    <Field label="Password" htmlFor="password" required error={errors.password}>
                        <TextInput
                            id="password"
                            type="password"
                            value={password}
                            autoComplete="current-password"
                            onChange={(e) => setPassword(e.target.value)}
                            required
                        />
                    </Field>
                    <Button type="submit" disabled={submitting} className="w-full">
                        {submitting ? 'Signing in…' : 'Sign in'}
                    </Button>
                    <p className="text-center text-sm">
                        <Link
                            to="/forgot-password"
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            Forgot password?
                        </Link>
                    </p>
                    <p className="pt-2 text-center text-xs text-gray-500">
                        Test accounts: <code>admin@example.com</code>, <code>manager1@example.com</code>,
                        <code>employee1@example.com</code> · password <code>password123</code>
                    </p>
                </form>
            </div>
        </div>
    );
}
