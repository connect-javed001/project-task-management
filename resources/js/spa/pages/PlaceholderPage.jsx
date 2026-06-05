import PageHeader from '../components/PageHeader';
import EmptyState from '../components/ui/EmptyState';

export default function PlaceholderPage({ title, description }) {
    return (
        <div>
            <PageHeader title={title} />
            <EmptyState
                title="Coming soon"
                description={description ?? 'This section will be implemented in a later slice.'}
            />
        </div>
    );
}
