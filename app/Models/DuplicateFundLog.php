<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateFundLog extends Model
{
    use HasFactory;

    public function fund() {
        return $this->belongsTo(Fund::class);
    }
}
