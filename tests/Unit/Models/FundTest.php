<?php

namespace Tests\Unit\Models;

use App\Models\DuplicateFundLog;
use App\Models\Fund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;


class FundTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Creation of funds or alises with previously existing names should add a record 
     *  to the DuplicateFundLog.
     * This is more of an integration test, since it check the side effect of creating funds/aliases.
     */
    public function testGenerateDuplicateEventsAndLogsForEqualNames()
    {
        /**
         * Create a bunch of funds with the same name, or aliases containing the same name
         * and verify that the log table is properly populated
         */

        $name = $this->faker->unique()->company;

        $fund1 = Fund::factory()->hasAliases(1, ['name' => $name])->create();

        $fund2 = Fund::factory()->create([
            'name' => $name
        ]);

        $this->assertEquals(2, DuplicateFundLog::count());

        $funds = collect(range(1, 5))->map(fn() => Fund::factory()->create([
            'name' => $name
        ]));

        $fund8 = Fund::factory()->hasAliases(1, ['name' => $name])->create();

        $expectedIds = $funds->pluck('id')->push($fund1->id)->push($fund2->id)->push($fund8->id);
        
        $this->assertEquals(8, $expectedIds->count());

        $this->assertEquals(
            $expectedIds->count(), 
            DuplicateFundLog::count()
        );

        $duplicateName = DuplicateFundLog::whereIn('id', $expectedIds)->first()->duplicate_name;

        $this->assertEquals(
            $expectedIds->count(), 
            DuplicateFundLog::where('duplicate_name', $duplicateName)->count()
        );
    }

    public function testChangingNameRecomputeDuplicationLogs()
    {
        $name = $this->faker->unique()->company;

        $fund1 = Fund::factory()->create(['name' => $name]);

        $fund2 = Fund::factory()->create(['name' => $name]);

        $this->assertEquals(2, DuplicateFundLog::count());

        $fund1->name = $this->faker->unique()->name;
        $fund1->update();

        $this->assertEquals(0, DuplicateFundLog::count());
    }

    public function testRemoveAliasRecomputeDuplicateLogs()
    {
        $name = $this->faker->unique()->company;

        $fund1 = Fund::factory()->hasAliases(1, ['name' => $name])->create();

        $fund2 = Fund::factory()->create(['name' => $name]);

        $this->assertEquals(2, DuplicateFundLog::count());

        $fund1->aliases()->first()->delete();

        $this->assertEquals(0, DuplicateFundLog::count());
    }
}