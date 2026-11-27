<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->attemptLogin('budi@example.com', 'salah-'.$attempt)
                ->assertHasErrors('form.email');
        }

        $component = $this->attemptLogin('budi@example.com', 'password');

        $component->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_successful_login_resets_the_attempt_counter(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        foreach (range(1, 4) as $attempt) {
            $this->attemptLogin('budi@example.com', 'salah-'.$attempt)
                ->assertHasErrors('form.email');
        }

        $this->attemptLogin('budi@example.com', 'password')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        Auth::logout();

        foreach (range(1, 4) as $attempt) {
            $component = $this->attemptLogin('budi@example.com', 'salah-'.$attempt);

            $this->assertSame(
                trans('auth.failed'),
                $component->errors()->get('form.email')[0] ?? null,
                'Percobaan gagal setelah login sukses tidak boleh langsung terkunci.',
            );
        }
    }

    public function test_spoofed_forwarded_for_header_cannot_bypass_the_email_limit(): void
    {
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->setTrustedProxies(['0.0.0.0/0'], Request::HEADER_X_FORWARDED_FOR);

        $this->app->instance('request', $request);

        $throttle = new LoginThrottle;

        foreach (range(1, 10) as $attempt) {
            $request->headers->set('X-Forwarded-For', '203.0.113.'.$attempt);

            $throttle->hit('korban@example.com');
        }

        $request->headers->set('X-Forwarded-For', '198.51.100.200');

        $this->expectException(ValidationException::class);

        $throttle->ensureIsNotRateLimited('korban@example.com', 'form.email');
    }

    public function test_different_email_has_its_own_counter(): void
    {
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.2');

        $this->app->instance('request', $request);

        $throttle = new LoginThrottle;

        foreach (range(1, 5) as $attempt) {
            $throttle->hit('korban@example.com');
        }

        $throttle->ensureIsNotRateLimited('pengguna-lain@example.com', 'form.email');

        $this->addToAssertionCount(1);
    }

    private function attemptLogin(string $email, string $password): Testable
    {
        return Volt::test('pages.auth.login')
            ->set('form.email', $email)
            ->set('form.password', $password)
            ->call('login');
    }
}
