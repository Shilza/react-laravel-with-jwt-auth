<?php

namespace App\Http\Middleware;

use App\Utils\RefreshHelper\RefreshHelper;
use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;


class VerifyRefreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {

            if(!JWTAuth::getToken())
                return response()->json(['error' => 'access_token_absent'], 400);
            if(!$refresh = $request->header('Refresh'))
                return response()->json(['error' => 'refresh_token_absent'], 400);
            if (!RefreshHelper::attempt($refresh))
                return response()->json(['error' => 'user_not_found'], 404);

        } catch (TokenExpiredException $e) {
            return response()->json($e->getMessage(), $e->getCode());
        } catch (TokenInvalidException $e) {
            return response()->json($e->getMessage(), $e->getCode());
        }  catch (JWTException $e) {
            return response()->json($e->getMessage(), $e->getCode());
        }

        return $next($request);
    }
}
