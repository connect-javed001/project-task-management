import { useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import Button from '../components/ui/Button';
import { Field, TextInput } from '../components/ui/Field';
import { forgotPassword } from '../api/auth';
import { formatErrors } from '../lib/format';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [sent, setSent] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await forgotPassword(email);
            setSent(true);
            toast.success('If that email exists, a reset link has been sent.');
        } catch (err) {
            toast.error(formatErrors(err).message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 to-indigo-50 px-4">
            <div className="w-full max-w-md">
                <div className="mb-6 text-center">
                    <h1 className="text-2xl font-bold text-gray-900">Forgot password</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Enter your email and we'll send you a reset link.
                    </p>
                </div>
                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-lg bg-white p-6 shadow ring-1 ring-gray-200"
                >
                    <Field label="Email" htmlFor="email" required>
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
                    <Button type="submit" disabled={submitting || sent} className="w-full">
                        {submitting ? 'Sending…' : sent ? 'Email sent' : 'Send reset link'}
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
