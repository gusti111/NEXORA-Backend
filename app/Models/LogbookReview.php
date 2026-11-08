<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookReview extends Model
{
    protected $fillable = [
        'logbook_id',
        'dosen_id',
        'status',
        'komentar',
    ];

    // Logbook yang diperiksa
    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class, 'logbook_id');
    }

    // Dosen yang melakukan pemeriksaan
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }
}
