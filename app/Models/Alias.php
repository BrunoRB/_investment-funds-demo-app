<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Alias extends Model
{
    use HasFactory;

    protected $table = 'aliases';

    protected $fillable = ['name', 'fund_id'];

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }
}
