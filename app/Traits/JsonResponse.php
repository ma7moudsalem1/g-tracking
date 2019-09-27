<?php

namespace App\Traits;

trait JsonResponse{

    public function responseSuccess($result = [], $message = '')
    {
        return response()->json(['status' => 1, 'message' => $message, 'data' => $result], 200);
    }

    public function responseFail($message = '', $result = [], $status = 400)
    {
        return response()->json(['status' => 0, 'message' => $message, 'data' => $result], $status);
    }

    public function responseErrors($errors = [], $result = [], $message = 'operation failed', $status = 422)
    {
        return response()->json(['status' => 0, 'errors' => $errors, 'message' => $message, 'data' => $result], $status);
    }

}