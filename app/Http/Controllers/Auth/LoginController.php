<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Utils\RefreshHelper\RefreshHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\Http\Controllers\Api\UserController;



class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        $access = auth()->attempt($credentials);

        if (!$access)
            return response()->json(['error' => 'Unauthorized'], 401);

        $user = auth()->user();
        $refresh = RefreshHelper::generateRefresh($user->id);
        User::where('id', $user->id)->update(['refresh_token' => $refresh]);

        return response()->json([
            'user'  => $user,
            'access_token' => $access,
            'expires_in' => time() + auth()->factory()->getTTL() * 60,
            'refresh_token' => $refresh
        ],200);
    }

    public function socialLogin($social)
    {
        if ($social == "facebook" || $social == "google" || $social == "linkedin") {
            return Socialite::driver($social)->stateless()->redirect();
        } else {
            return Socialite::driver($social)->redirect();           
        }
    }

    public function handleProviderCallback($social)
    {
        if ($social == "facebook" || $social == "google" || $social == "linkedin") {
            $userSocial = Socialite::driver($social)->stateless()->user();
        } else {
            $userSocial = Socialite::driver($social)->user();           
        }
        
        $token = $userSocial->token;
        
        $user = User::firstOrNew(['email' => $userSocial->getEmail()]);

        if (!$user->id) {
            $user->fill(["name" => $userSocial->getName(),"password"=>bcrypt(str_random(6))]);
            $user->save();
        }

        return response()->json([
            'user'  => [$user],
            'userSocial'  => $userSocial,
            'token' => $token,
        ],200);
    }

}
