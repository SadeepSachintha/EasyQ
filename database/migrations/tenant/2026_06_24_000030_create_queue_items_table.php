<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_items', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $blueprint->string('customer_name');
            $blueprint->string('customer_phone')->nullable();
            $blueprint->string('ticket_number');
            $blueprint->string('service_name')->nullable();
            $blueprint->enum('status', ['waiting', 'in-service', 'completed', 'no-show'])->default('waiting');
            $blueprint->integer('estimated_wait_minutes')->default(0);
            $blueprint->timestamp('called_at')->nullable();
            $blueprint->timestamp('completed_at')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_items');
    }
};
