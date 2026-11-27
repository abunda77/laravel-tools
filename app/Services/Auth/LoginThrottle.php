<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pembatas percobaan login (anti brute force) yang dipakai bersama oleh
 * login web (/login) dan login API (/api/v1/auth/login).
 *
 * Tiga kunci dipakai sekaligus supaya satu kontrol tidak bisa dilewati sendiri:
 * - email + IP : batas ketat untuk satu mesin yang menyerang satu akun.
 * - email saja : batas per akun, tetap berlaku walau penyerang memalsukan
 *                X-Forwarded-For sehingga IP terlihat berganti-ganti.
 * - IP saja    : batas kasar untuk satu sumber yang menebak banyak akun.
 *
 * Kunci di-hash agar tidak menyimpan email/IP mentah di tabel cache.
 */
class LoginThrottle
{
    /**
     * Lempar ValidationException bila percobaan login sudah melewati batas.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(string $email, string $errorKey = 'email'): void
    {
        $lockedKey = $this->lockedKey($email);

        if ($lockedKey === null) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($lockedKey);

        throw ValidationException::withMessages([
            $errorKey => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Catat satu percobaan login yang gagal.
     */
    public function hit(string $email): void
    {
        foreach (array_keys($this->limits()) as $type) {
            RateLimiter::hit($this->key($type, $email), $this->decaySeconds());
        }
    }

    /**
     * Hapus hitungan percobaan setelah login berhasil.
     */
    public function clear(string $email): void
    {
        foreach (array_keys($this->limits()) as $type) {
            RateLimiter::clear($this->key($type, $email));
        }
    }

    /**
     * Batas percobaan per jenis kunci.
     *
     * @return array<string, int>
     */
    private function limits(): array
    {
        return [
            'email_ip' => (int) config('tools.login_throttle.max_attempts_per_email_and_ip', 5),
            'email' => (int) config('tools.login_throttle.max_attempts_per_email', 10),
            'ip' => (int) config('tools.login_throttle.max_attempts_per_ip', 30),
        ];
    }

    /**
     * Cari kunci yang sudah terkunci dan punya waktu tunggu paling lama.
     */
    private function lockedKey(string $email): ?string
    {
        $lockedKey = null;
        $longestWait = 0;

        foreach ($this->limits() as $type => $maxAttempts) {
            $key = $this->key($type, $email);

            if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                continue;
            }

            $seconds = RateLimiter::availableIn($key);

            if ($seconds > $longestWait) {
                $longestWait = $seconds;
                $lockedKey = $key;
            }
        }

        return $lockedKey;
    }

    private function key(string $type, string $email): string
    {
        $subject = match ($type) {
            'email' => 'email:'.$this->normalizeEmail($email),
            'ip' => 'ip:'.$this->ip(),
            default => $this->normalizeEmail($email).'|'.$this->ip(),
        };

        return 'login:'.$type.':'.hash('sha256', $subject);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::transliterate(Str::lower(trim($email)));
    }

    private function ip(): string
    {
        return request()->ip() ?? 'unknown';
    }

    private function decaySeconds(): int
    {
        return (int) config('tools.login_throttle.decay_seconds', 60);
    }
}
