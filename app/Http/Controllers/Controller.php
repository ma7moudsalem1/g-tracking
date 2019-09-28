<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\JsonResponse;
use App\Traits\FirebaseTrait;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use JsonResponse, FirebaseTrait;
    protected function buildFailedValidationResponse(Request $request, array $errors) {
        return $this->responseErrors($errors, $request->all());
    }
}
