<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['IN', 'OUT', 'BREAK_IN', 'BREAK_OUT']);
            $table->dateTime('ts_server', 6);
            $table->dateTime('ts_client', 6)->nullable();
            $table->string('ip', 45);
            $table->text('user_agent')->nullable();
            $table->json('device_info')->nullable();
            $table->char('fingerprint_hash', 64)->nullable();
            $table->json('geo')->nullable();
            $table->boolean('geo_consent')->default(false);
            $table->string('observacao', 255)->nullable();
            $table->string('source', 16)->default('web');
            $table->timestamps();

            $table->index(['user_id', 'ts_server']);
            $table->index('fingerprint_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punches');
    }
};
