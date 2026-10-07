import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Beranda" />
            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center">
                <AppLogoIcon className="size-12 fill-current text-foreground" />
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Ganti halaman ini dengan beranda project Anda.
                    </p>
                </div>
                <Button asChild>
                    <Link href={auth.user ? dashboard() : login()}>
                        {auth.user ? 'Buka dashboard' : 'Masuk'}
                    </Link>
                </Button>
            </div>
        </>
    );
}
