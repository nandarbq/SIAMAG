<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = ['date', 'name', 'source'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
