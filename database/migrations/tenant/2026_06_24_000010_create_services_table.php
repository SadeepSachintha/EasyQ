<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->text('description')->nullable();
            $blueprint->decimal('price', 10, 2);
            $blueprint->integer('duration_minutes');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
