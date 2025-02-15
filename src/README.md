<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

1. **Salin file konfigurasi**
   ```sh
   copy .env.example .env
   ```

2. **Jalankan Docker Compose**
   ```sh
   docker-compose up -d
   ```

3. **Jalankan migrasi database**
   ```sh
   php artisan migrate
   ```

4. **Isi database dengan data awal (seeding)**
   ```sh
   php artisan db:seed
   ```

5. **Buat symbolic link untuk penyimpanan file**
   ```sh
   php artisan storage:link
   ```

6. **Jalankan queue worker**
   ```sh
   php artisan queue:work
   ```

7. **Jalankan queue worker untuk antrean spesifik**
   ```sh
   php artisan queue:work --queue=kp_count,ibtitah,sidang
   ```



