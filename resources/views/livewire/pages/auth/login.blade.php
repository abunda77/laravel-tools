<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="auth-login-shell">
    <div class="auth-ambient auth-ambient--one"></div>
    <div class="auth-ambient auth-ambient--two"></div>

    <main class="auth-login-stage">
        <section class="auth-login-intro" aria-labelledby="login-heading">
            <a href="/" wire:navigate class="auth-login-brand" aria-label="{{ config('app.name', 'Laravel Tools') }}">
                <x-application-logo class="auth-login-logo" />
                <span>{{ config('app.name', 'Laravel Tools') }}</span>
            </a>

            <div class="auth-login-copy">
                <p class="auth-login-kicker">Secure workspace</p>
                <h1 id="login-heading">Masuk ke dashboard operasional.</h1>
                <p>
                    Kelola automasi, API, dan workflow internal dari satu ruang kerja yang rapi.
                </p>
            </div>

            <div class="auth-signal-panel" aria-hidden="true">
                <div class="auth-signal-panel__header">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="auth-signal-panel__body">
                    <div class="auth-signal-panel__meter auth-signal-panel__meter--wide"></div>
                    <div class="auth-signal-panel__meter"></div>
                    <div class="auth-signal-panel__grid">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-login-panel" aria-label="Login form">
            <div class="auth-login-panel__heading">
                <p>Welcome back</p>
                <h2>Sign in</h2>
            </div>

            <x-auth-session-status class="auth-session-status" :status="session('status')" />

            <form wire:submit="login" class="auth-login-form">
                <div class="auth-login-field">
                    <label for="email">Email</label>
                    <input
                        wire:model="form.email"
                        id="email"
                        class="auth-login-input"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                    >
                    <x-input-error :messages="$errors->get('form.email')" class="auth-login-error" />
                </div>

                <div class="auth-login-field">
                    <label for="password">Password</label>
                    <input
                        wire:model="form.password"
                        id="password"
                        class="auth-login-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                    <x-input-error :messages="$errors->get('form.password')" class="auth-login-error" />
                </div>

                <div class="auth-login-options">
                    <label for="remember" class="auth-login-check">
                        <input wire:model="form.remember" id="remember" type="checkbox" name="remember">
                        <span>{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>

                <button type="submit" class="auth-login-submit">
                    <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
                    <span wire:loading wire:target="login" class="auth-login-submit__loading">
                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Processing
                    </span>
                </button>
            </form>
        </section>
    </main>
</div>
