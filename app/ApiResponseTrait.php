<?php

namespace App;

use Illuminate\Http\JsonResponse;

Trait ApiResponseTrait{
    public function parseGivenData(array $data=[], int $statusCode =200,array $headers = [] ):array{
        //success, messsage, result, errors, exception, status, error_code
        $responseStructure = [
            'success' => $data['success'] ?? false,
            'statusCode'=> $statusCode,
            'message' => $data['message'] ?? null,

        ] ;

        if(isset($data['data'])){
            $responseStructure['data'] = $data['data'] ?? null;
        }

        if(isset($data['errors'])){
            $responseStructure['errors'] = $data['errors'];
        }

        if(isset($data['status'])){
            $responseStructure['status'] = $data['status'];
        }

        if(isset($data['exception'])){
            if($data['exception'] instanceof \Error || $data['exception'] instanceof \Exception)
            if(config(key: 'app.env' !=='production')){
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage(),
                    'file' => $data['exception']->getFile(),
                    'line' => $data['exception']->getLine(),
                    'code' => $data['exception']->getCode(),
                    'trace' => $data['exception']->getTrace(),
                ] ;
            }

            if($statusCode ==200){
                $statusCode = 500;
            }
        }

        if($data['success'] == false){
            isset($data['error_code']) && $responseStructure['error_code'] = $data['error_code'];
        }

        return [
            'content'=> $responseStructure,
            'statusCode'=> $statusCode,
            'headers'=>$headers
        ];
    }

    public function apiResponse (array $data=[], int $statusCode =200,array $headers = [] ):JsonResponse{
        $result = $this->parseGivenData( $data, $statusCode, $headers );
        return response()->json($result['content'], $result['statusCode'], $result['headers']);
    }

    public function sendSuccess (mixed $data, string $message = ''):JsonResponse{
        return $this->apiResponse([
            'success'=>true,
            'message'=> $message,
            'data'=> $data,
        ]);
    }

    public function sendError (string $message = '', int $statusCode = 400,\Exception $exception = null):JsonResponse{
        return $this->apiResponse(
            [
                'success'=>false,
                'message'=> $message,
                'exception'=> $exception,
            ],
            $statusCode,
        );
    }

    public function sendUnauthorized (string $message = 'Unauthorized'){
        return $this->sendError($message);
    }

    public function sendForbidden (string $message = 'Forbidden'){
        return $this->sendError($message);
    }

    public function sendInternalServerError (string $message = 'Internal Server Error'){
        return $this->sendError($message);
    }

}
