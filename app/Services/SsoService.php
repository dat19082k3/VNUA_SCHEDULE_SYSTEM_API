<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoService
{
    /**
     * Lấy thông tin user từ hệ thống SSO bằng access_token
     */
    public function getUserData(array $token): array | null{
        try{
            // Gửi yêu cầu đến hệ thống SSO để lấy thông tin người dùng
            $response = Http::withHeaders([
                'Authorization' => $token['token_type'] . ' ' . $token['access_token'],
                'Accept' => 'application/json',
            ])->withoutVerifying()->get(config('auth.sso.uri') . '/api/user');

            // Kiểm tra xem yêu cầu có thành công hay không
            if ($response->successful()) {
                // Phân tích dữ liệu JSON từ phản hồi
                $data = $response->json();

                // Trả về dữ liệu người dùng
                return $data;
            }
            return null;
        }
        catch (\Throwable $e) {
            // Ghi lại bất kỳ lỗi nào xảy ra
            Log::error('SSO integration error', ['message' => $e->getMessage()]);
            return null;
        }
    }
    /**
     * Lấy thông tin user từ hệ thống SSO bằng mã code
     */

    public function getUserFromSsoCode(string $code): array | null
    {
        try {
            // Send the authorization code to get the access token
            $response = Http::asForm()->withOptions([
                'verify' => false, // Disable SSL verification (not recommended for production)
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ])->post(config('auth.sso.uri') . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('auth.sso.client_id'),
                'client_secret' => config('auth.sso.client_secret'),
                'redirect_uri' => config('auth.sso.redirect_uri'),
                'code' => $code,
            ]);

            // Check if the response is successful
            if (!$response->successful()) {
                Log::error('SSO token request failed', ['response' => $response->body()]);
                return null;
            }

            // Parse the response JSON
            $data = $response->json();

            Log::info('SSO token request successful', ['data' => $data]);

            // Return the token data
            return $data ?? null;
        } catch (\Throwable $e) {
            // Log any errors that occur
            Log::error('SSO integration error', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
