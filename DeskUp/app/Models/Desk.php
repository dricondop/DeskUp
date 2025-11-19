<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desk extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'desk_number',
        'api_desk_id',
        'position_x',
        'position_y',
        'status',
        'height',
        'speed',
        'is_active'
    ];

    protected $casts = [
        'height' => 'integer',
        'speed' => 'integer',
        'is_active' => 'boolean',
        'position_x' => 'float',
        'position_y' => 'float'
    ];

    public function activities()
    {
        return $this->hasMany(DeskActivity::class);
    }

    /**
     * Check if desk is connected to API simulator
     */
    public function isConnectedToAPI(): bool
    {
        return !is_null($this->api_desk_id);
    }

    /**
     * Get formatted position for display
     */
    public function getFormattedHeightAttribute(): string
    {
        return $this->height . ' cm';
    }
}
