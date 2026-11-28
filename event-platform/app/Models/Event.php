<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'event_date',
        'location',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price' => 'decimal:2',
        ];
    }
}
