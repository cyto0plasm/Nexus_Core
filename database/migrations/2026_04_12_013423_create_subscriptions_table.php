<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Paymob references
        $table->string('paymob_order_id')->nullable();
        $table->string('paymob_transaction_id')->nullable();

        $table->enum('plan', ['monthly', 'yearly']);
        $table->decimal('amount', 10, 2);
        $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');

        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
