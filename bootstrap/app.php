<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (Throwable $e, Request $request) {

            \Log::error('withExceptions', [$e]);

            if ($e instanceof AuthenticationException) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated.'], 401);
                }
                return redirect()->route('login');
            }
            if ($e instanceof Illuminate\Auth\AuthenticationException) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            if ($e instanceof ValidationException) {
                \Log::error('$e->validator', [$e->validator->errors()]);
                $errors = $e->validator->errors();
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ], 422);
            }
            if ($e instanceof \Illuminate\Database\QueryException) {
                if ($e->getCode() == 23000) {
                    return response()->json([
                        'message' => 'No se puede eliminar porque tiene registros relacionados.'
                    ], 409);
                }
            }
            return (new \Illuminate\Foundation\Exceptions\Handler(app()))->render($request, $e);
        });
        //
    })->create();
