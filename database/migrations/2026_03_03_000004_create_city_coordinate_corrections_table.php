<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_coordinate_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->decimal('old_lat', 10, 7);
            $table->decimal('old_lng', 10, 7);
            $table->decimal('new_lat', 10, 7);
            $table->decimal('new_lng', 10, 7);
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['city_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_coordinate_corrections');
    }
};
