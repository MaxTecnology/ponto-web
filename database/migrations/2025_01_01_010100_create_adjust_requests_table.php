<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjust_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('from_ts', 6)->nullable();
            $table->dateTime('to_ts', 6)->nullable();
            $table->text('reason');
            $table->enum('status', ['PENDENTE', 'APROVADO', 'REJEITADO'])->default('PENDENTE');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at', 6)->nullable();
            $table->json('audit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjust_requests');
    }
};
