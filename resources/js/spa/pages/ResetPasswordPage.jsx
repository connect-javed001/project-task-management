import { useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import Button from '../components/ui/Button';
import { Field, TextInput } from '../components/ui/Field';
import { resetPassword } from '../api/auth';
import { formatErrors } from '../lib/format';

export default function ResetPasswordPage() {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const [email, setEmail] = useState(searchParams.get('email') ?? '');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState({});

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            await resetPassword({
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            toast.success('Password reset. Please sign in.');
            navigate('/login', { replace: true });
        } catch (err) {
            const { message, fields } = formatErrors(err);
            setErrors(fields);
            toast.error(fields.email || fields.password || message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 to-indigo-50 px-4">
            <div className="w-full max-w-md">
                <div className="mb-6 text-center">
                    <h1 className="text-2xl font-bold text-gray-900">Reset password</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Choose a new password for your account.
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
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />
                    </Field>
                    <Field
                        label="New password"
                        htmlFor="password"
                        required
                        error={errors.password}
                        hint="At least 8 characters"
                    >
                        <TextInput
                            id="password"
                            type="password"
                            value={password}
                            autoComplete="new-password"
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            minLength={8}
                        />
                    </Field>
                    <Field
                        label="Confirm new password"
                        htmlFor="password_confirmation"
                        required
                    >
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            value={passwordConfirmation}
                            autoComplete="new-password"
                            onChange={(e) => setPasswordConfirmation(e.target.value)}
                            required
                            minLength={8}
                        />
                    </Field>
                    <Button type="submit" disabled={submitting} className="w-full">
                        {submitting ? 'Saving…' : 'Reset password'}
                    </Button>
                    <p className="pt-2 text-center text-sm">
                        <Link to="/login" className="text-indigo-600 hover:text-indigo-800">
                            Back to sign in
                        </Link>
                    </p>
                </form>
            </div>
        </div>
    );
}
