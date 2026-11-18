<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canteen_transaction_id');
            $table->unsignedBigInteger('canteen_item_id')->nullable();
            $table->string('item_name');
            $table->string('unit', 20)->default('pcs');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('canteen_transaction_id')->references('id')->on('canteen_transactions')->onDelete('cascade');
            $table->foreign('canteen_item_id')->references('id')->on('canteen_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_transaction_items');
    }
};
