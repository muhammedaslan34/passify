<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org.member'  => \App\Http\Middleware\VerifyOrganizationMembership::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect on 419 (session/CSRF expired) so user gets a fresh page instead of "Page Expired" dialog.
        // Fix production: set SESSION_SECURE_COOKIE=true and SESSION_DOMAIN=null (or your domain) in .env.
        $exceptions->respond(function (mixed $response, \Throwable $e, Request $request) {
            if ($e instanceof TokenMismatchException || ($e instanceof HttpException && $e->getStatusCode() === 419)) {
                return redirect()
                    ->back()
                    ->withInput($request->except('password', '_token'))
                    ->with('error', __('Session expired. Please try again.'));
            }
            return null;
        });
    })->create();
