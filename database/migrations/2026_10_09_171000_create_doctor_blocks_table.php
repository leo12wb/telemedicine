<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('block_date');
            $table->time('block_start')->nullable()->comment('null = bloqueio de dia inteiro');
            $table->time('block_end')->nullable()->comment('null = bloqueio de dia inteiro');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'block_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_blocks');
    }
};
