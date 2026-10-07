<?php

namespace Tests\Feature;

use App\Exceptions\BusinessException;
use App\Exceptions\ProtectedRecordException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BusinessExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::post('/_test/business', fn () => throw new BusinessException('Stok tidak cukup.', 409, 'out_of_stock'));
            Route::post('/_test/protected', fn () => throw new ProtectedRecordException('Data dikunci.'));
        });
    }

    public function test_json_clients_get_the_message_code_and_status(): void
    {
        $this->postJson('/_test/business')
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Stok tidak cukup.', 'code' => 'out_of_stock']);

        $this->postJson('/_test/protected')->assertStatus(409)->assertJsonPath('code', 'protected_record');
    }

    public function test_inertia_requests_are_sent_back_with_an_error_toast(): void
    {
        $this->from('/dashboard')
            ->withHeaders(['X-Inertia' => 'true'])
            ->post('/_test/business')
            ->assertRedirect('/dashboard')
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Stok tidak cukup.']);
    }

    public function test_business_exceptions_are_not_reported(): void
    {
        Log::spy();

        $this->postJson('/_test/business');

        Log::shouldNotHaveReceived('error');
    }
}
