<?php

namespace App\Livewire\Forms;

use App\Services\Auth\LoginThrottle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $throttle = app(LoginThrottle::class);

        $throttle->ensureIsNotRateLimited($this->email, 'form.email');

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            $throttle->hit($this->email);

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $throttle->clear($this->email);
    }
}
