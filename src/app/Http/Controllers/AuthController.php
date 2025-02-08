<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $identifier = $request->input('identifier');
        $password = $request->input('password');

        // Log input login
        Log::info('Login attempt:', ['identifier' => $identifier]);

        // URL untuk login mahasiswa
        $urlMahasiswa = env('API_URL') . '/Auth/loginKalam';

        // Log URL yang digunakan
        Log::info('API URL Mahasiswa:', ['url' => $urlMahasiswa]);

        try {
            // Melakukan login via API untuk mahasiswa
            $responseMahasiswa = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'), // Sertakan token dari .env
                'Content-Type' => 'application/json', // Tambahkan jika API memerlukan header ini
            ])->post($urlMahasiswa, [
                'username' => $identifier,
                'password' => $password,
            ]);

            // Log respon API
            Log::info('API Response Mahasiswa:', ['response' => $responseMahasiswa->json()]);

            $dataMahasiswa = $responseMahasiswa->json();

            // Jika login berhasil sebagai mahasiswa
            if (isset($dataMahasiswa['status']) && $dataMahasiswa['status'] === true && $dataMahasiswa['status_login'] === 'Mahasiswa') {
                // Simpan data login ke sesi
                $request->session()->put('user', [
                    'id' => $dataMahasiswa['data']['id'], // ID mahasiswa
                    'username' => $dataMahasiswa['data']['username'],
                    'nim' => $dataMahasiswa['data']['username'], // NIM
                    'name' => $dataMahasiswa['data']['first_name'] . ' ' . $dataMahasiswa['data']['last_name'],
                    'role' => 'mahasiswa',
                    'token' => env('API_TOKEN'), // Simpan token juga ke sesi
                ]);

                Log::info('Session user after login (Mahasiswa):', ['user' => $request->session()->get('user')]);

                return redirect()->route('dashboard'); // Redirect ke dashboard mahasiswa
            }
        } catch (\Exception $e) {
            // Log jika terjadi error saat permintaan API
            Log::error('Error calling API Mahasiswa:', ['message' => $e->getMessage()]);
        }

        // Login sebagai dosen/tendik melalui API
        try {
            $urlDosenTendik = 'https://sip.uinsgd.ac.id/sip_module/ws/login';
            Log::info('API URL Dosen/Tendik:', ['url' => $urlDosenTendik]);

            // Kirim token, username, dan password melalui form-data
            $responseDosen = Http::asForm()->post($urlDosenTendik, [
                'token' => env('DOSEN_API_TOKEN'), // Token diambil dari .env
                'username' => $identifier,
                'password' => $password,
            ]);

            Log::info('API Response Dosen/Tendik:', ['response' => $responseDosen->json()]);

            $dataDosen = $responseDosen->json();

            // Validasi apakah respons menunjukkan keberhasilan
            if (isset($dataDosen['code']) && $dataDosen['code'] === '1') {
                // Simpan data login dosen/tendik ke sesi
                $request->session()->put('user', [
                    'id' => $dataDosen['id'],
                    'username' => $identifier,
                    'name' => $dataDosen['nama'],
                    'email' => $dataDosen['profil']['email'] ?? null,
                    'role' => 'admin', // Role diset admin untuk dosen/tendik
                    'foto' => $dataDosen['foto'] ?? null, // Tambahkan foto jika diperlukan
                ]);

                Log::info('Session user after login (Dosen/Tendik):', ['user' => $request->session()->get('user')]);

                return redirect()->route('admin.dashboard'); // Redirect ke dashboard admin
            } else {
                // Log jika respons menunjukkan error
                Log::error('Invalid token or credentials for Dosen/Tendik', ['response' => $dataDosen]);
                return back()->withErrors(['login' => 'Token atau kredensial tidak valid.']);
            }
        } catch (\Exception $e) {
            // Log jika terjadi error saat permintaan API
            Log::error('Error during login process for Dosen/Tendik:', ['message' => $e->getMessage()]);
            return back()->withErrors(['login' => 'Terjadi kesalahan saat memproses login.']);
        }

        // Autentikasi admin dari database lokal
        $admin = Admin::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($admin && Hash::check($password, $admin->password)) {
            // Simpan data admin ke sesi
            $request->session()->put('user', [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ]);

            Log::info('Admin login successful:', ['user' => $request->session()->get('user')]);

            return redirect()->route('admin.dashboard'); // Redirect ke dashboard admin
        }

        // Jika login gagal
        Log::warning('Login failed:', ['identifier' => $identifier]);

        return back()->withErrors(['login' => 'Username atau Password salah!']);
    }

    public function logout(Request $request)
    {
        // Log sebelum logout
        Log::info('User logged out:', ['user' => $request->session()->get('user')]);

        // Hapus data dari sesi
        $request->session()->forget('user');

        // Redirect ke halaman login
        return redirect()->route('login.form');
    }
}
