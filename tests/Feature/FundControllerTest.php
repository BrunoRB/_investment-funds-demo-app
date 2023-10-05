<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FundControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * basic listing check
     * assert data is paginated
     * assert all entities are present
     * some basic field matching
     */
    public function testListFunds()
    {
        $fundsKeyById = Fund::factory()->count(3)->hasAliases(3)->create()->keyBy('id');

        $response = $this->get('/api/funds');

        $response->assertOk();

        $data = $response->json();

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(3, $data['total']);
        $this->assertEquals(3, count($data['data']));


        foreach($data['data'] as $fundResponseData) {
            $this->assertArrayHasKey('id', $fundResponseData);
            $this->assertArrayHasKey('name', $fundResponseData);
            $this->assertArrayHasKey('aliases', $fundResponseData);

            $this->assertTrue($fundsKeyById->has($fundResponseData['id']));
            $fund = $fundsKeyById->get($fundResponseData['id']);
            $this->assertEquals($fundResponseData['name'], $fund->name);
            $aliasesByKey = $fund->aliases->keyBy('id');
            foreach($fundResponseData['aliases'] as $aliasData) {
                $this->assertTrue($aliasesByKey->has($aliasData['id']));
                $this->assertEquals($aliasData['name'], $aliasesByKey->get($aliasData['id'])->name);
            }
        }

    }

    /**
     * list funds with some filters
     */
    public function testFilteringListFunds()
    {
        // bunch of random funds
        Fund::factory(
            ['start_year' => '2022', 'name' => 'somename' . rand(999, 9999)]
        )->count(3)->hasAliases(3)->create();
        Fund::factory(
            ['start_year' => '2025', 'name' => 'anothername' . rand(999, 9999)]
        )->count(3)->create();


        $fund2023 = Fund::factory([
            'start_year' => '2023'
        ])->create();

        $fundWithCertainName = Fund::factory([
            'name' => 'SomeFundWithSpecificName',
            'start_year' => '2029'
        ])->create();


        $fundManager = FundManager::factory(['name' => 'evil-manager5555'])->hasFunds(1)->create();

        $response = $this->get('/api/funds?year=2023');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($response->json()['data'][0]['id'], $fund2023->id);


        $response = $this->get('/api/funds?name=somefundWithSpecificname&year=2029');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($response->json()['data'][0]['id'], $fundWithCertainName->id);

        $response = $this->get('/api/funds?manager=evil-manager5555');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($response->json()['data'][0]['id'], $fundManager->funds()->first()->id);
    }

    /** 
     * Full attribute update, including aliases replacement
    */
    public function testUpdateFund()
    {
        $fund = Fund::factory()->hasAliases(3)->create();

        $newManager = FundManager::factory()->create();

        $response = $this->put("/api/funds/{$fund->id}", [
            'name' => 'New Fund Name',
            'start_year' => '5445',
            'aliases' => ['new alias 1', 'new alias 2'],
            'fund_manager_id' => $newManager->id
        ]);


        $response->assertOk();
        $data = $response->json();

        $this->assertEquals($data['id'], $fund->id);
        $this->assertEquals($data['name'], 'New Fund Name');
        $this->assertEquals($data['start_year'], '5445');
        $this->assertEquals($data['fund_manager']['id'], $newManager->id);
        $this->assertEquals(2, count($data['aliases']));
    }

    /**
     * Ensure
     * 
     *  - we list all duplicate "groups"
     *  - they have a proper count of possible duplicates
     *  - they display the duplicated name
     */
    public function testListPotentiallyDuplicateFunds()
    {
        $name = $this->faker->unique()->company;

        $x = Fund::factory()->count(3)->hasAliases(1, ['name' => $name])->create();
        $y = Fund::factory()->count(2)->create(['name' => $name]);
        $groupOne = $x->merge($y);


        $groupTwoName = $this->faker->unique()->company;
        $a = Fund::factory()->count(1)->hasAliases(1, ['name' => $groupTwoName])->create();
        $b = Fund::factory()->count(3)->create(['name' => $groupTwoName]);
        $groupTwo = $a->merge($b);

        $groupThreeName = $this->faker->unique()->company;
        $z = Fund::factory()->count(4)->hasAliases(1, ['name' => $groupThreeName])->create();
        $w = Fund::factory()->count(6)->create(['name' => $groupThreeName]);
        $groupThree = $z->merge($w);
        
        $response = $this->get('/api/funds/duplicates');

        $json = $response->json();
        $data = collect($json['data'])->keyBy('duplicate_name');

        // we have 3 duplicate groups
        $this->assertEquals(3, $json['total']);

        $this->assertTrue($data->has($name));
        $this->assertTrue($data->has($groupTwoName));
        $this->assertTrue($data->has($groupThreeName));

        $this->assertEquals($groupOne->count(), $data->get($name)['number_of_duplicates']);
        $this->assertEquals($groupTwo->count(), $data->get($groupTwoName)['number_of_duplicates']);
        $this->assertEquals($groupThree->count(), $data->get($groupThreeName)['number_of_duplicates']);

        // TODO check fund_ids list
    }
}