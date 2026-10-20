<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalPlaylistTrack extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'local_playlist_id',
        'track_id',
        'position',
        'added_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'added_at' => 'datetime',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(LocalPlaylist::class, 'local_playlist_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }
}
