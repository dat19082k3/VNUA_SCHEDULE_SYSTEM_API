<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('api', [
            App\Http\Middleware\EnfoceJsonResponse::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ]);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //Exception Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Unauthenticated. Please login to continue.', [
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'input' => $request->all(),
                ]);

                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;


                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Unauthenticated. Please login to continue.',
                ];

                return response()->json($response, $statusCode);
            }
        });

        //Exception NotFoundHttpException
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Validation Exception', [
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'url' => $request->fullUrl(),
                    'input' => $request->all(),
                ]);

                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

                // Chỉ lấy lỗi đầu tiên của mỗi field
                $firstErrors = collect($e->errors())->map(fn($errors) => $errors[0])->toArray();

                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Validation failed',
                    'errors' => $firstErrors, // Chỉ lấy lỗi đầu tiên
                ];

                return response()->json($response, $statusCode);
            }
        });


        //Exception ValidatonException
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Validation Exception', [
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'url' => $request->fullUrl(),
                    'input' => $request->all(),
                ]);

                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

                // Chỉ lấy lỗi đầu tiên của mỗi field
                $firstErrors = collect($e->errors())->map(fn($errors) => $errors[0])->toArray();

                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Validation failed',
                    'errors' => $firstErrors, // Chỉ lấy lỗi đầu tiên
                ];

                return response()->json($response, $statusCode);
            }
        });

        //Exception NotFoundHttpException
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error($e->getMessage());

                $statusCode = Response::HTTP_NOT_FOUND;

                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Resource could not be found',
                ];

                // Nếu không phải production, có thể trả thêm exception để debug
                if (config('app.env') !== 'production') {
                    $response['exception'] = $e->getMessage();
                }

                return response()->json($response, $statusCode);
            }
        });

        //Exception UniqueConstraintViolationException
        $exceptions->render(function (UniqueConstraintViolationException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Database unique constraint violation', [
                    'message' => $e->getMessage(),
                    'url' => $request->url(),
                    'inputs' => $request->all(),
                ]);

                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;  // Phù hợp hơn cho lỗi này

                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Duplicate entry found',
                ];

                // Ở môi trường không phải production thì show thêm lỗi cụ thể
                if (config('app.env') !== 'production') {
                    $response['exception'] = $e->getMessage();
                }

                return response()->json($response, $statusCode);
            }
        });

        //Exception QueryException
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error($e->getMessage());

                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

                $response = [
                    'success' => false,
                    'statusCode' => $statusCode,
                    'message' => 'Could not execute query',
                ];

                // Nếu không phải production, có thể trả thêm exception để debug
                if (config('app.env') !== 'production') {
                    $response['exception'] = $e->getMessage();
                }

                return response()->json($response, $statusCode);
            }
        });

        //Exception Exception
        $exceptions->render(function (\Exception $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('General Exception', [
                    'exception' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'input' => $request->all(),
                ]);

                $response = [
                    'success' => false,
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'An unexpected error occurred, please try again later',
                ];

                if (config('app.env') !== 'production') {
                    $response['exception'] = $e->getMessage();
                }

                return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });

        //Exception Error
        $exceptions->render(function (\Error $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Fatal Error', [
                    'exception' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'input' => $request->all(),
                ]);

                $response = [
                    'success' => false,
                    'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'A system error occurred',
                ];

                if (config('app.env') !== 'production') {
                    $response['exception'] = $e->getMessage();
                }

                return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    })->create();
