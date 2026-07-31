<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        /*
         * The mobile API must ALWAYS answer JSON in the same envelope the
         * success path uses, whatever the client sent in Accept. Without this a
         * Spatie role failure renders an HTML 403 page and the Flutter client
         * dies on `jsonDecode`, showing "unexpected character" instead of
         * "sizda ruxsat yo'q".
         */
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $this->apiResponse($e);
        });
    }

    /**
     * Map any exception onto { ok:false, message, errors? } with a sane status.
     */
    private function apiResponse(Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'ok'      => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'ok'      => false,
                'message' => 'Tizimga kiring.',
            ], 401);
        }

        if ($e instanceof UnauthorizedException) {
            return response()->json([
                'ok'      => false,
                'message' => 'Sizda bu amal uchun ruxsat yo‘q.',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'ok'      => false,
                'message' => 'Ma’lumot topilmadi.',
            ], 404);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage() ?: match ($status) {
                    403     => 'Sizda bu amal uchun ruxsat yo‘q.',
                    404     => 'Topilmadi.',
                    429     => 'Juda ko‘p so‘rov. Biroz kuting.',
                    default => 'Xatolik yuz berdi.',
                },
            ], $status);
        }

        // Unexpected: the message is only exposed with APP_DEBUG on, so a
        // stack trace never leaks to a phone in production.
        return response()->json([
            'ok'      => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'Serverda xatolik yuz berdi.',
        ], 500);
    }
}
