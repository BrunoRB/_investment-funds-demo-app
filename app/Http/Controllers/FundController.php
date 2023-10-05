<?php

namespace App\Http\Controllers;

use App\Models\DuplicateFundLog;
use Illuminate\Http\Request;
use App\Models\Fund;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FundController extends Controller
{    
    public function list(Request $request): LengthAwarePaginator
    {
         $query = Fund::query()->with('aliases')->with('fundManager');
 
         // Filter by name
         if ($request->has('name')) {
            $name = strtolower($request->input('name'));
            $query->whereRaw('LOWER(name)=?', [$name]);
         }
 
         if ($request->has('manager')) {
            $manager_name = strtolower(trim($request->input('manager')));
            $query->whereHas('fundManager', function ($query) use ($manager_name) {
                $query->whereRaw('LOWER(name)=?', [$manager_name]);
            });
         }

        if ($request->has('year')) {
            $query->where('start_year', $request->input('year'));
        }
 
         return $query->paginate(50);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'start_year' => 'string|min:4|max:4',
            'fund_manager_id' => 'exists:fund_managers,id',
            'aliases' => 'array',
            'aliases.*' => 'string|max:255',
        ]);
    
        $fund = Fund::with('aliases')->with('fundManager')->findOrFail($id);

        if (!$validated) {
            return $fund;
        }

        DB::transaction(function() use ($fund, $validated) {

            $fund->name = $validated['name'] ?? $fund->name;
            $fund->start_year = $validated['start_year'] ?? $fund->start_year;
            $fund->fund_manager_id = $validated['fund_manager_id'] ?? $fund->fund_manager_id;
            $fund->save(); // laravel will detect if there are any changes and only update if there are
        

            if (isset($validated['aliases'])) {
                $fund->aliases()->delete();
                if ($validated['aliases']) {
                    $fund->aliases()->createMany(
                        array_map(
                            fn($aliasName) => ['name' => $aliasName],
                            $validated['aliases']
                        )
                    );
                }
            }

        });

        $fund->refresh();
        
        return $fund;
    }

    public function duplicates() 
    {
        return DuplicateFundLog::select(
            'duplicate_name', DB::raw('count(*) as number_of_duplicates'), DB::raw('group_concat(fund_id) as fund_ids')
        )
            ->groupBy('duplicate_name')
            ->paginate(50);
    }
}
