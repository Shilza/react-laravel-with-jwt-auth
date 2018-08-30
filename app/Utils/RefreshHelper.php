<?php

namespace App\Utils\RefreshHelper;

use App\User;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;


class RefreshHelper{

    public static function generateRefresh($userId)
    {
        // Create token header as a JSON string
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // Create token payload as a JSON string
        $payload = json_encode(['iat' => time(), 'exp' => Carbon::now()->addDays(30)->timestamp, 'sub' => $userId]);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, env('JWT_REFRESH_SECRET'), true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Create JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * @param $token
     * @return bool
     * @throws JWTException
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     */
    public static function attempt($token){
        $tks = explode('.', $token);
        if (count($tks) != 3)
            throw new TokenInvalidException('refresh_token_invalid', 400);

        list($headb64, $bodyb64, $cryptob64) = $tks;
        $header = static::jsonDecode(static::urlsafeB64Decode($headb64));
        $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64));
        $sig = static::urlsafeB64Decode($cryptob64);

        if (!static::verify("$headb64.$bodyb64", $sig, env('JWT_REFRESH_SECRET')))
            throw new JWTException('refresh_token_invalid', 401);

        if($payload->exp < time())
            throw new TokenExpiredException('refresh_token_expired', 401);

        if(!$user = User::where('id', $payload->sub)->first())
            return false;

        $user->makeVisible(['refresh_token']);

        if($user->refresh_token !== $token)
            throw new JWTException('refresh_token_invalid', 401);

        return true;
    }

    public static function getIdFromToken($token){
        return static::jsonDecode(static::urlsafeB64Decode(explode('.', $token)[1]))->sub;
    }

    private static function verify($msg, $signature, $key)
    {

        $hash = hash_hmac('SHA256', $msg, $key, true);

        if (function_exists('hash_equals')) {
            return hash_equals($signature, $hash);
        }

        $len = min(static::safeStrlen($signature), static::safeStrlen($hash));
        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= (ord($signature[$i]) ^ ord($hash[$i]));
        }
        $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));
        return ($status === 0);
    }

    private static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            $max_int_length = strlen((string)PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }

        return $obj;
    }

    private static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }
}

