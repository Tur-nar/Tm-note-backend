<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_note_id',
        'target_note_id',
        'user_id',
    ];

    // ── Relationships ──

    public function sourceNote()
    {
        return $this->belongsTo(Note::class, 'source_note_id');
    }

    public function targetNote()
    {
        return $this->belongsTo(Note::class, 'target_note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
