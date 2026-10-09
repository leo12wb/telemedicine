<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique(); // 1:1 com users
            $table->string('cpf', 14)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('health_insurance', 100)->nullable();
            $table->string('health_insurance_number', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
