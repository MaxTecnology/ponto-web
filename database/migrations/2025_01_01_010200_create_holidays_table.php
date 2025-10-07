<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->enum('scope', ['NACIONAL', 'UF', 'MUNICIPIO', 'EMPRESA']);
            $table->char('uf', 2)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->date('date');
            $table->string('name', 100);
            $table->timestamps();

            $table->index(['scope', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
