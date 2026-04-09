<?php

use App\Exceptions\CustomExcepiton;
use App\Http\Middleware\XssCleanMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // XSS Sanitization Middleware - Provides additional input validation layer
        // NOTE: Primary defense is output encoding in JSON responses (automatic in Laravel)
        // Uncomment to enable if needed for form data processing
        // $middleware->append(XssCleanMiddleware::class);

        // JWT Authentication Middleware - Protect API routes
        // $middleware->append(JwtMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (ValidationException $exception): JsonResponse {
            $errors = $exception->validator->errors()->all();

            Log::error($exception->getMessage());

            return response()->json([
                'message' => 'Dados inválidos',
                'error' => $errors,
            ], 422);
        });
        $exceptions->render(function (CustomExcepiton $exception): JsonResponse {

            Log::error($exception->getMessage());

            return response()->json([
                'message' => $exception->getMessage(),
                'error' => $exception->getMessage(),
            ], 404);
        });
        // Verifica se o erro 404 foi causado por um Model não encontrado e lança a execeção
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            // Verifica se o erro 404 foi causado por um Model não encontrado
            if ($exception->getPrevious() instanceof ModelNotFoundException) {
                $originalException = $exception->getPrevious();

                Log::error($originalException->getMessage());

                return response()->json([
                    'message' => 'Registro para o id especificado não foi encontrado',
                    'error' => $originalException->getMessage(),
                ], 404);
            }
        });

        // Intercepta a exceção de N+1 (Lazy Loading Violation)
        $exceptions->render(function (LazyLoadingViolationException $exception, Request $request) {
            Log::error("Lazy Loading Violation: " . $exception->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Query N+1 detectada! Faltou o eager loading (carregamento prévio) na consulta.',
                    'developer_hint' => sprintf(
                        "Você deve usar ->with('%s') para carregar esta relação no model %s antes de acessá-la.",
                        $exception->relation,
                        class_basename($exception->model)
                    ),
                    'error_details' => "A relação [{$exception->relation}] não foi carregada. Lazy loading está desativado.",
                ], 500);
            }
        });

        $exceptions->render(function (\Throwable $exception): JsonResponse {
            Log::error("Exception: " . $exception->getMessage());
            // LOG::channel('telegram')->error("Final Exception: " . $exception->getMessage());

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            // SECURITY: Only expose detailed error info in debug mode
            $response = [
                'message' => 'Ocorreu um erro interno no servidor. Tente novamente mais tarde.',
            ];

            if (config('app.debug')) {
                $response['error'] = $exception->getMessage();
                $response['exception'] = class_basename($exception);
            }

            return response()->json($response, $status);
        });

    })->create();
