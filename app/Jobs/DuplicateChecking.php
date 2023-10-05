<?php

namespace App\Jobs;

use App\Events\DuplicateFundWarning;
use App\Models\DuplicateFundLog;
use App\Models\Fund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * TODO we should "linearize" this queue, 
 *  so that we don't have 2 jobs running at the same time (even for different funds). 
 * 
 * Laravel has some abstractions for job uniqueness, but they discard duplicates, and that's not what we want.
 * 
 */
class DuplicateChecking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $fundId;
    public ?string $nameForDeletion;

    public function __construct(int $fundId, ?string $nameForDeletion = null)
    {
        $this->fundId = $fundId;
        $this->nameForDeletion = $nameForDeletion;
    }

    public function handle(): void
    {
        $fund = Fund::find($this->fundId);
        if (!$fund) {
            return;
        }

        DB::transaction(function() use ($fund) {
            /**
             * If the name changed (or we removed an alias), we need to recompute the log table.
             * 
             * We delete our own fund from it, and also delete the "other one" 
             *  if there are only 2 funds left.
             * 
             * Then we recompute the log table for the new name.
             */
            if ($this->nameForDeletion) {
                DuplicateFundLog::where('duplicate_name', $this->nameForDeletion)->delete();
            }

            $this->detectDuplicates($fund);
        });
    }


    /**
     * Detect if this fund has possible duplicates based on its name or aliases n
     */
    public function detectDuplicates(Fund $fund) 
    {
        $names = array_merge(
            [$fund->name],
            $fund->aliases->pluck('name')->map(fn($name) => $name)->toArray()
        );

        $duplicateFundsIds = Fund::where(function($query) use ($names) {
            $query->whereIn('name', $names)
                ->orWhereHas('aliases', function ($query) use($names) {
                    $query->whereIn('name', $names);
                });
        })->where('id', '!=', $fund->id)
        ->pluck('id')
        ->toArray();

        if ($duplicateFundsIds) {
            DuplicateFundWarning::dispatch($fund->id, $duplicateFundsIds);
        }
    }

}
