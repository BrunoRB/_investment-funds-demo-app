<?php

namespace Database\Factories;

use App\Models\Alias;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Factories\Factory;

class AliasFactory extends Factory
{
    protected $model = Alias::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'fund_id' => Fund::factory(),
        ];
    }
}