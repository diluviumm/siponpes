<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('transaction_date');
            $table->enum('type', ['pemasukan', 'pengeluaran']);
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transactions');
    }
};
