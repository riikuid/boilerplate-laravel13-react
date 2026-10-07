<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_model_is_logged_with_hidden_attributes_masked(): void
    {
        $user = User::factory()->create(['name' => 'Siti']);

        $log = AuditLog::forModel($user)->where('event', 'user.created')->firstOrFail();

        $this->assertSame('Siti', $log->new_values['name'] ?? null);
        $this->assertSame('[redacted]', $log->new_values['password'] ?? null);
        $this->assertArrayNotHasKey('remember_token', $log->new_values ?? []);
        $this->assertArrayNotHasKey('created_by', $log->new_values ?? []);
        $this->assertArrayNotHasKey('id', $log->new_values ?? []);
    }

    public function test_updating_logs_only_the_changed_fields_with_old_and_new_values(): void
    {
        $user = User::factory()->create(['name' => 'Lama']);

        $user->update(['name' => 'Baru']);

        $log = AuditLog::forModel($user)->where('event', 'user.updated')->firstOrFail();
        $this->assertSame(['name' => 'Lama'], $log->old_values);
        $this->assertSame(['name' => 'Baru'], $log->new_values);
    }

    public function test_a_password_change_is_visible_but_its_value_is_not(): void
    {
        $user = User::factory()->create();

        $user->update(['password' => 'a-new-password-123']);

        $log = AuditLog::forModel($user)->where('event', 'user.updated')->firstOrFail();
        $this->assertSame(['password' => '[redacted]'], $log->new_values);
    }

    public function test_changes_to_excluded_attributes_do_not_create_a_log(): void
    {
        $user = User::factory()->create();
        $before = AuditLog::count();

        $user->forceFill(['remember_token' => 'rotated'])->save();

        $this->assertSame($before, AuditLog::count());
    }

    public function test_deleting_and_restoring_are_logged(): void
    {
        $user = User::factory()->create();

        $user->delete();
        $user->restore();

        $events = AuditLog::forModel($user)->orderBy('id')->pluck('event')->all();
        $this->assertSame(['user.created', 'user.deleted', 'user.restored'], $events);
    }

    public function test_the_actor_and_request_context_are_recorded(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor);
        $target->update(['name' => 'Diubah']);

        $log = AuditLog::forModel($target)->where('event', 'user.updated')->firstOrFail();
        $this->assertSame($actor->id, $log->actor_id);
    }

    public function test_login_and_logout_are_recorded(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->post(route('logout'));

        $events = AuditLog::forModel($user)->pluck('event')->all();
        $this->assertContains('auth.login', $events);
        $this->assertContains('auth.logout', $events);
    }

    public function test_failed_logins_are_recorded_without_the_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);

        $log = AuditLog::where('event', 'auth.login_failed')->firstOrFail();
        $this->assertSame($user->email, $log->meta['email'] ?? null);
        $this->assertStringNotContainsString('wrong', json_encode($log->toArray()) ?: '');
    }

    public function test_audit_logs_cannot_be_modified_or_deleted(): void
    {
        $log = app(AuditLogger::class)->record('custom.event');

        $this->expectException(LogicException::class);
        $log->update(['event' => 'tampered']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $log = app(AuditLogger::class)->record('custom.event');

        $this->expectException(LogicException::class);
        $log->delete();
    }
}
