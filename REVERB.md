# Notifikasi Realtime Laravel Reverb

Notifikasi dokumen menggunakan Laravel Reverb, Laravel Echo, dan channel privat per pengguna.

## Menjalankan aplikasi lokal

Buka tiga terminal dari folder proyek:

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

```powershell
php artisan reverb:start --host=127.0.0.1 --port=8080
```

```powershell
npm run watch
```

Untuk server produksi, jalankan Reverb dan queue worker menggunakan process manager agar otomatis hidup kembali. Implementasi event notifikasi saat ini memakai `ShouldBroadcastNow`, sehingga queue worker tidak diperlukan khusus untuk event ini.

## Arsitektur

- Channel privat: `private-users.{user_uuid}`
- Event: `.documents.updated`
- Otorisasi: `routes/channels.php`
- Event backend: `app/Events/DocumentNotificationUpdated.php`
- Penentuan penerima: `app/Services/DocumentNotificationBroadcaster.php`
- Client Echo: `resources/js/realtime.js`
- Fallback: feed HTTP setiap 30 detik hanya saat WebSocket terputus

Setelah mengubah `resources/js/realtime.js`, jalankan:

```powershell
npm run development
```
