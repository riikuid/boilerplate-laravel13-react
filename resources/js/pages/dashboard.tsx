import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-6 p-4">
                <PageHeader
                    title={`Selamat datang, ${auth.user.name}`}
                    description="Ganti halaman ini dengan dashboard project Anda."
                />
                <div className="flex flex-wrap gap-2">
                    {auth.roles.map((role) => (
                        <Badge key={role} variant="secondary">
                            {role}
                        </Badge>
                    ))}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
