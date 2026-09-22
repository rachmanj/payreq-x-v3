<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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

        $permissionDeniedJson = fn () => response()->json([
            'responseMessage' => 'You do not have the required authorization.',
            'responseStatus' => 403,
            'error' => 'forbidden',
            'reason' => 'permission',
            'message' => 'Anda tidak memiliki izin untuk aksi ini.',
        ], 403);

        $permissionDeniedRedirect = fn () => redirect()
            ->back()
            ->with('alert_type', 'error')
            ->with('alert_title', 'Access Denied')
            ->with('alert_message', 'You do not have the required permissions to perform this action.');

        $this->renderable(function (UnauthorizedException $e, $request) use ($permissionDeniedJson, $permissionDeniedRedirect) {
            if ($request->expectsJson()) {
                return $permissionDeniedJson();
            }

            return $permissionDeniedRedirect();
        });

        $this->renderable(function (AccessDeniedHttpException $e, $request) use ($permissionDeniedJson) {
            if ($request->expectsJson()) {
                return $permissionDeniedJson();
            }
        });
    }
}
