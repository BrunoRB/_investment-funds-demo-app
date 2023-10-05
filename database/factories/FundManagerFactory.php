<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\FundManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundManagerFactory extends Factory
{
    protected $model = FundManager::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company
        ];
    }
}