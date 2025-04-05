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
        Schema::create('expensify_logins', function (Blueprint $table) {
            $table->id();
            $table->string('slack_user_id')->unique();
            $table->string('partner_id');
            $table->text('password'); // Will be encrypted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expensify_logins');
    }
};
