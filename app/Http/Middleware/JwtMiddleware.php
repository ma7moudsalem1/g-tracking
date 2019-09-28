<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use App\Traits\JsonResponse;

class JwtMiddleware
{
    use JsonResponse;

    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->get('token');
        
        if(!$token) {
            // Unauthorized response if token not there
            return $this->responseFail('Token not provided.', $request->all(), 401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return $this->responseFail('Provided token is expired.', $request->all(), 400);
        } catch(Exception $e) {
            return $this->responseFail('An error while decoding token.', $request->all(), 400);
        }

        $user = User::find($credentials->sub);
        if(!$user){
            return $this->responseFail('An error while decoding token.', $request->all(), 400);
        }
        $request->auth = $user;
        return $next($request);
    }
}
