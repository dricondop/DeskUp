<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeskActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'desk_id',
        'activity_type',
        'description',
        'scheduled_at',
        'scheduled_to',
        'status'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'scheduled_to' => 'datetime'
    ];

    public function desk()
    {
        return $this->belongsTo(Desk::class);
    }
}
