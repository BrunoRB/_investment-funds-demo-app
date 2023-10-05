<?php

namespace App\Models;

use App\Jobs\DuplicateChecking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::created(function (Fund $fund) {
            DuplicateChecking::dispatch($fund->id);
        });

        static::updated(function (Fund $fund) {
             if ($fund->isDirty('name')) {
                DuplicateChecking::dispatch($fund->id, $fund->getOriginal()['name']);
             }
        });

        Alias::created(function (Alias $alias) {
            DuplicateChecking::dispatch($alias->fund->id);
        });

        Alias::deleted(function (Alias $alias) {
            DuplicateChecking::dispatch($alias->fund->id, $alias->name);
        });
    }

    /**
     * Compare two funds and find if they have a duplicate name or alias
     */
    public function findDuplicateName(Fund $comparisonFund): ?string
    {
        $names1 = array_merge(
            [$this->name],
            $this->aliases->pluck('name')->map(fn($name) => $name)->toArray()
        );
        $names2 = array_merge(
            [$comparisonFund->name],
            $comparisonFund->aliases->pluck('name')->map(fn($name) => $name)->toArray()
        );

        foreach(array_intersect($names1, $names2) as $name) {
            return $name;
        }

        return null;
    }

    public function aliases()
    {
        return $this->hasMany(Alias::class);
    }

    public function fundManager()
    {
        return $this->belongsTo(FundManager::class);
    }

}
