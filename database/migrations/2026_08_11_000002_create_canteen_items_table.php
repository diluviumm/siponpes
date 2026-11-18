<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canteen_id');
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('canteen_id')->references('id')->on('canteens')->onDelete('cascade');
            $table->unique(['canteen_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_items');
    }
};
