<?php

namespace App\Exceptions;

use Throwable;
use App\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    protected $dontFlash =[
        'current_password',
        'password',
        'password_confirmation'
    ];

    public function render($request,Throwable $e){
        if($request->expectsJson()){
            Log::error($e);
            if($e instanceof NotFoundHttpException){
                $statusCode = Response::HTTP_NOT_FOUND;
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => $statusCode,
                    'message' => 'Resource could be found',
                    'exception' => $e,
                ]);
            }

            if($e instanceof UniqueConstraintViolationException){
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                return $this->apiResponse([
                    'message' => 'Duplicate entry found',
                    'success' => false,
                    'exception' => $e,
                    'error_code' => $statusCode,
                ]);
            }

            if($e instanceof QueryException){
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                return $this->apiResponse([
                    'success' => false,
                    'error_code' => $statusCode,
                    'message' => 'Could not execute query',
                    'exception' => $e,
                ]);
            }
        }
        return parent::render($request, $e);
    }
}
