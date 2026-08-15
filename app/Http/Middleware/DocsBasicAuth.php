<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocsBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('docs.basic_auth.username');
        $password = config('docs.basic_auth.password');

        if (empty($username) || empty($password)) {
            return $next($request);
        }

        $authUsername = $request->getUser();
        $authPassword = $request->getPassword();

        if ($authUsername !== $username || $authPassword !== $password) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="API Documentation"',
            ]);
        }

        return $next($request);
    }
}
