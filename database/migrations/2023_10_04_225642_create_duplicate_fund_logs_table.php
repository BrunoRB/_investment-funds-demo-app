<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_fund_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('fund_id');
            $table->foreign('fund_id')->references('id')->on('funds');
            
            $table->string('duplicate_name')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_fund_logs');
    }
};
