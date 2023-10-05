<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies_funds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('fund_id');
            $table->foreign('fund_id')->references('id')->on('funds');


            $table->unique(['company_id', 'fund_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies_funds');
    }
};
