<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email? : Email address} {--name= : Display name}';

    protected $description = 'Create a super admin account (also seeds permissions and roles)';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        $email = (string) ($this->argument('email') ?? text('Email', required: true));

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->components->error("A user with email {$email} already exists.");

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?? text('Name', default: 'Super Admin', required: true));
        $secret = (string) password('Password', required: true);

        $user = new User(['name' => $name, 'email' => $email, 'password' => $secret]);
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole(RoleName::SuperAdmin->value);

        $this->components->info("Super admin {$email} created.");

        return self::SUCCESS;
    }
}
