<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomLoginController extends Controller
{
    public function loginAndFetchProfile(Request $request)
    {
        $identifier = $request->input('identifier'); // NIM atau username
        $password = $request->input('password');

        // Log input login
        Log::info('CustomLoginController: Login attempt.', ['identifier' => $identifier]);

        // URL untuk login environment KALAM
        $urlLoginKalam = env('API_URL') . '/Auth/loginKalam';

        try {
            // Melakukan login via API KALAM
            $responseLogin = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_URL'),
                'Content-Type' => 'application/json',
            ])->post($urlLoginKalam, [
                'username' => $identifier,
                'password' => $password,
            ]);

            // Log response login
            Log::info('CustomLoginController: Login response.', ['response' => $responseLogin->json()]);

            $dataLogin = $responseLogin->json();

            // Jika login berhasil
            if (isset($dataLogin['status']) && $dataLogin['status'] === true) {
                $nim = $dataLogin['data']['username']; // Asumsi username adalah NIM
                $token = env('API_TOKEN1'); // Token digunakan untuk HEROKU

                // Fetch profile mahasiswa dari environment HEROKU
                $urlProfileHeroku = env('API_URL1') . '/Api/ProfileMhs';

                $responseProfile = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get($urlProfileHeroku, [
                    'nim' => $nim,
                    'token' => $token,
                ]);

                // Log response profile
                Log::info('CustomLoginController: Profile response.', ['response' => $responseProfile->json()]);

                $dataProfile = $responseProfile->json();

                if (isset($dataProfile['status']) && $dataProfile['status'] === true) {
                    // Berhasil mengambil profile
                    return response()->json([
                        'status' => true,
                        'message' => 'Login dan pengambilan data profil berhasil.',
                        'data' => $dataProfile['data'],
                    ]);
                } else {
                    // Gagal mengambil profile
                    return response()->json([
                        'status' => false,
                        'message' => 'Gagal mengambil data profil dari HEROKU.',
                    ]);
                }
            } else {
                // Gagal login
                return response()->json([
                    'status' => false,
                    'message' => 'Login gagal, periksa username dan password.',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CustomLoginController: Error during login or fetching profile.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat proses login atau pengambilan data.',
            ]);
        }
    }
}
