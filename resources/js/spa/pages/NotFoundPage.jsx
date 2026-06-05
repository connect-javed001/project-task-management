import { Link } from 'react-router-dom';
import Button from '../components/ui/Button';

export default function NotFoundPage() {
    return (
        <div className="rounded-lg border-2 border-dashed border-gray-200 px-6 py-16 text-center">
            <h2 className="text-lg font-semibold text-gray-900">Page not found</h2>
            <p className="mt-1 text-sm text-gray-500">
                The page you're looking for doesn't exist yet.
            </p>
            <div className="mt-4">
                <Link to="/dashboard">
                    <Button>Back to dashboard</Button>
                </Link>
            </div>
        </div>
    );
}
