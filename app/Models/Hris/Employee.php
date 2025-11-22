<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // Koneksi pakai mysql_hris (didefinisikan di config/database.php)
    protected $connection = 'mysql_hris';

    // Nama tabel di DB HRIS
    protected $table = 'employees';

    protected $primaryKey = 'id';

    public $timestamps = false; // kalau tabel employees tidak pakai created_at/updated_at

    protected $fillable = [
        'name',
        'email',
        // tambahkan field lain kalau perlu
    ];
}
