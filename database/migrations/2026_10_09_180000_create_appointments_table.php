<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors');
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('status')->default(AppointmentStatus::AGENDADA->value);
            $table->text('notes')->nullable()->comment('Anotações clínicas do médico');
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users');
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Impede double-booking: mesmo médico, mesma data/hora, status ativo
            // Nota: unique parcial (excluindo canceladas) é aplicado na camada de serviço
            // + índice composto para consultas por médico/data/status
            $table->index(['doctor_id', 'scheduled_date', 'status']);
            $table->index(['patient_id', 'scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
