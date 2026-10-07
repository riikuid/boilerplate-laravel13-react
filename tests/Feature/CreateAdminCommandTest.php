<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_super_admin(): void
    {
        $this->artisan('app:create-admin', ['email' => 'root@example.com', '--name' => 'Root'])
            ->expectsQuestion('Password', 'a-strong-password-1')
            ->assertSuccessful();

        $user = User::where('email', 'root@example.com')->firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_refuses_an_existing_email(): void
    {
        User::factory()->create(['email' => 'root@example.com']);

        $this->artisan('app:create-admin', ['email' => 'root@example.com', '--name' => 'Root'])->assertFailed();
    }
}
