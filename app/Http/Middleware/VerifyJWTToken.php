<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;


class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (!JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'user_not_found'], 404);

        } catch (TokenExpiredException $e) {

            return response()->json(['error' => 'token_expired'], 401);

        } catch (TokenInvalidException $e) {

            return response()->json(['error' => 'token_invalid'], 401);

        } catch (JWTException $e) {

            return response()->json(['error' => 'token_absent'], 400);
        }


        return $next($request);
    }
}
