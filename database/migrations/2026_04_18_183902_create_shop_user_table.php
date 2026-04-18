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
        Schema::create ('shop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('cashier');
            $table->boolean('is_active')->default(true); // can deactivate a cashier without deleting
            $table->timestamps();

            $table->unique(['shop_id', 'user_id']); // can't join same shop twice
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_user', function (Blueprint $table) {
            Schema::dropIfExists('shop_user');
        });
    }
};
