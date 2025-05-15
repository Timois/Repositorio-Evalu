<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        $this->renderable(function (UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'mensaje' => 'No tienes permisos para realizar esta acción',
                ], 403);
            }
            
            return redirect()->route('home')->with('error', 'No tienes permisos para realizar esta acción');
        });
    }
    public function render($request, Throwable $exception)
    {   
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'error' => 'Token inválido o sesión cerrada'
            ], 401);
        }

        return parent::render($request, $exception);
    }
}
