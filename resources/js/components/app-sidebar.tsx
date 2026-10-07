import { Link } from '@inertiajs/react';
import { LayoutGrid, ScrollText, ShieldCheck, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Permission } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as auditLogs } from '@/routes/audit-logs';
import { index as roles } from '@/routes/roles';
import { index as users } from '@/routes/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
];

// Add new modules here; an item shows only when the user holds its permission.
const adminNavItems: NavItem[] = [
    {
        title: 'Pengguna',
        href: users(),
        icon: Users,
        permission: Permission.User.Read,
    },
    {
        title: 'Peran & Hak Akses',
        href: roles(),
        icon: ShieldCheck,
        permission: Permission.Role.Read,
    },
    {
        title: 'Audit Log',
        href: auditLogs(),
        icon: ScrollText,
        permission: Permission.AuditLog.Read,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain label="Menu" items={mainNavItems} />
                <NavMain label="Administrasi" items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
