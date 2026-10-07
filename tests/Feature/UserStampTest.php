<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStampTest extends TestCase
{
    use RefreshDatabase;

    public function test_stamps_follow_the_authenticated_user(): void
    {
        $creator = User::factory()->create();
        $editor = User::factory()->create();

        $this->actingAs($creator);
        $record = User::factory()->create();

        $this->assertSame($creator->id, $record->created_by);
        $this->assertSame($creator->id, $record->updated_by);

        $this->actingAs($editor);
        $record->update(['name' => 'Changed']);
        $this->assertSame($creator->id, $record->fresh()?->created_by);
        $this->assertSame($editor->id, $record->fresh()?->updated_by);

        $record->delete();
        $this->assertSame($editor->id, User::withTrashed()->find($record->id)?->deleted_by);

        $record->restore();
        $this->assertNull($record->fresh()?->deleted_by);
    }

    public function test_nothing_is_stamped_without_an_authenticated_user(): void
    {
        $record = User::factory()->create();

        $this->assertNull($record->created_by);
        $this->assertNull($record->updated_by);
    }

    public function test_relations_resolve_to_users(): void
    {
        $creator = User::factory()->create();
        $this->actingAs($creator);

        $record = User::factory()->create();

        $this->assertTrue($record->creator?->is($creator));
    }
}
