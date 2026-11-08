<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Logbook extends Model
{
    
protected $fillable = [
        'pkl_id',
        'tanggal_kegiatan',
        'deskripsi_kegiatan',
        'foto',
        'latitude',
        'longitude',
        'status',
        'catatan_dosen',
    ];


    protected $casts = [
        'tanggal_kegiatan' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // Penempatan PKL
    public function pkl(): BelongsTo
    {
        return $this->belongsTo(Pkl::class, 'pkl_id');
    }

    // Riwayat pemeriksaan dosen
    public function reviews(): HasMany
    {
        return $this->hasMany(LogbookReview::class, 'logbook_id');
    }
}
