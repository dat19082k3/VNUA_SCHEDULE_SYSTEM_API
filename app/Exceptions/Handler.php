<?php

namespace App\Exceptions;

use Throwable;
use App\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation'
    ];

    protected $dontReport = [
        AuthorizationException::class,
        AuthenticationException::class,
        ValidationException::class,
    ];

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            Log::error($e);

            // Xử lý AuthorizationException (403 Forbidden)
            if ($e instanceof AuthorizationException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_FORBIDDEN,
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    'exception' => get_class($e),
                ], Response::HTTP_FORBIDDEN);
            }

            // Xử lý AuthenticationException (401 Unauthorized)
            if ($e instanceof AuthenticationException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'exception' => get_class($e),
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Xử lý ValidationException (422 Unprocessable Entity)
            if ($e instanceof ValidationException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'exception' => get_class($e),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Xử lý NotFoundHttpException (404 Not Found)
            if ($e instanceof NotFoundHttpException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Resource not found',
                    'exception' => get_class($e),
                ], Response::HTTP_NOT_FOUND);
            }

            // Xử lý MethodNotAllowedHttpException (405 Method Not Allowed)
            if ($e instanceof MethodNotAllowedHttpException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Method not allowed',
                    'exception' => get_class($e),
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            // Xử lý UniqueConstraintViolationException (409 Conflict)
            if ($e instanceof UniqueConstraintViolationException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry found',
                    'exception' => get_class($e),
                ], Response::HTTP_CONFLICT);
            }

            // Xử lý QueryException (500 Internal Server Error)
            if ($e instanceof QueryException) {
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Database query error',
                    'exception' => get_class($e),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Xử lý các exception khác (500 Internal Server Error)
            return $this->apiResponse([
                'success' => false,
                'error_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Internal server error',
                'exception' => get_class($e),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? $this->apiResponse([
                'success' => false,
                'error_code' => Response::HTTP_UNAUTHORIZED,
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'exception' => get_class($exception),
            ], Response::HTTP_UNAUTHORIZED)
            : redirect()->guest(route('login'));
    }
}
