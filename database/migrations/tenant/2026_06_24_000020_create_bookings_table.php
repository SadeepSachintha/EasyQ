<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('customer_name');
            $blueprint->string('customer_phone')->nullable();
            $blueprint->string('customer_email')->nullable();
            $blueprint->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $blueprint->date('booking_date');
            $blueprint->time('start_time');
            $blueprint->time('end_time');
            $blueprint->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
