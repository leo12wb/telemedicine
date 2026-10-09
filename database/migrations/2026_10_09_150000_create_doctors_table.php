<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('crm', 20);
            $table->char('crm_uf', 2);
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['crm', 'crm_uf']);
            $table->index('user_id');
            $table->index('is_active');
        });

        Schema::create('doctor_specialty', function (Blueprint $table) {
            $table->uuid('doctor_id');
            $table->uuid('specialty_id');

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('specialty_id')->references('id')->on('specialties')->cascadeOnDelete();
            $table->primary(['doctor_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_specialty');
        Schema::dropIfExists('doctors');
    }
};
