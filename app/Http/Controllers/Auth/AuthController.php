<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;
use App\Utils\RefreshHelper\RefreshHelper;
use JWTAuth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login', 'register', 'refresh']]);
    }

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
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Incorrect login or password'
            ], 401);

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

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(
            ['user' => auth()->user()], 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {

        User::whereId(auth()->user()->id)->update(['refresh_token' => '']);

        auth()->logout();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $id = RefreshHelper::getIdFromToken($request->header('Refresh'));
        $refresh = RefreshHelper::generateRefresh($id);
        User::whereId($id)->update(['refresh_token' => $refresh]);

        try{
            JWTAuth::parseToken()->authenticate();
        } finally {
            $refreshed = JWTAuth::refresh(JWTAuth::getToken());
            return $this->respondWithTokens($refreshed, $refresh);
        }
    }

    /**
     * @param $access
     * @param $refresh
     * @return \Illuminate\Http\JsonResponse
     */
    private function respondWithTokens($access, $refresh)
    {
        return response()->json([
            'access_token' => $access,
            'token_type' => 'bearer',
            'expires_in' => time() + auth()->factory()->getTTL() * 60,
            'refresh_token' => $refresh
        ], 200);
    }

    public function register(Request $request)
    {
        $validator =  Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }

        $request->merge(['password' => Hash::make($request->password),
            'refresh_token' => RefreshHelper::generateRefresh(User::count()+1)]);
        try{
            User::create($request->all());
            return response()->json(['status' => 'registered successfully'],200);
        }
        catch(Exception $e){
            return response()->json([
                "error" => "could_not_register",
                "message" => "Unable to register user"
            ], 400);
        }
    }
}
