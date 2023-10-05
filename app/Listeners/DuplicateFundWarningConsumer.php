<?php

namespace App\Listeners;

use App\Events\DuplicateFundWarning;
use App\Models\DuplicateFundLog;
use App\Models\Fund;
use Illuminate\Support\Facades\DB;

class DuplicateFundWarningConsumer 
{
    /**
     * whenever we find a list of possible duplicates, a "log" is recorded 
     * flagging the funds containing duplicate names, as well as 
     * 
     */
    public function handle(DuplicateFundWarning $event): void
    {
        $fund = Fund::find($event->fundId);
        $possibleDuplicates = Fund::whereIn('id', $event->possibleDuplicatesIds)->get();

        /**
         * This process may be running async (in a queue worker for instance),
         * which means that by the time it runs our fund may have been deleted 
         * and we have nothing to do
         */
        if (!$fund || !$possibleDuplicates->count()) {
            return;
        }

        $duplicateName = $fund->findDuplicateName($possibleDuplicates->first());

        $ids = [$fund->id];

        /**
         * Small edge case: for the first duplicate found for a given name, 
         * we have to flag the fund itself as a duplicate.
         * After that only the "newly found" duplicate neds to get flagged
         */
        if ($possibleDuplicates->count() === 1) {
            $ids[] = $possibleDuplicates->first()->id;
        }

        DB::transaction(function() use($ids, $duplicateName) {
            foreach($ids as $id) {
                $log = new DuplicateFundLog;
                $log->fund_id = $id;
                $log->duplicate_name = $duplicateName;
                $log->save();
            }
        });
    }
}
