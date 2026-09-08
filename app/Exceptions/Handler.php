<?php

namespace App\Exceptions;

use App\Exceptions\Agent\AgentNotVerifiedException;
use App\Exceptions\Agent\DocumentValidationException;
use App\Exceptions\Agent\InsufficientBalanceException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

        // Рендерим агентские исключения в JSON
        $this->renderable(function (InsufficientBalanceException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        $this->renderable(function (AgentNotVerifiedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }
        });

        $this->renderable(function (DocumentValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        // Обработка ModelNotFoundException
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "{$model} не найден",
                    'error' => 'not_found',
                ], 404);
            }

            abort(404, "{$model} не найден");
        }

        // Обработка NotFoundHttpException с моделью
        if ($e instanceof NotFoundHttpException && str_contains($e->getMessage(), 'No query results for model')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Запись не найдена',
                    'error' => 'not_found',
                ], 404);
            }
        }


        return parent::render($request, $e);
    }
}
