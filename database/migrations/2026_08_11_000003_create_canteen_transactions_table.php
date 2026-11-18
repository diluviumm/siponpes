<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteen_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->unsignedBigInteger('canteen_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->decimal('total', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->timestamps();

            $table->foreign('wallet_transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');
            $table->foreign('canteen_id')->references('id')->on('canteens')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_transactions');
    }
};
