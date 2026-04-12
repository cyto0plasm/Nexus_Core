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
        Schema::table('users', function (Blueprint $table) {
             $table->timestamp('trial_ends_at')->nullable()->after('password');
        $table->timestamp('subscribed_at')->nullable()->after('trial_ends_at');
        $table->timestamp('subscription_ends_at')->nullable()->after('subscribed_at');
        $table->enum('subscription_plan', ['monthly', 'yearly'])->nullable()->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn(['trial_ends_at', 'subscribed_at', 'subscription_ends_at', 'subscription_plan']);

        });
    }
};
