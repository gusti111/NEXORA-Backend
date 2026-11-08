<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pkl extends Model
{
    protected $fillable = [
        'mahasiswa_id',
        'dosen_id',
        'korlap_id',
        'nama_instansi',
        'alamat_instansi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'diverifikasi_oleh',
        'tanggal_selesai_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_selesai_verifikasi' => 'datetime',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function korlap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'korlap_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class, 'pkl_id');
    }
}