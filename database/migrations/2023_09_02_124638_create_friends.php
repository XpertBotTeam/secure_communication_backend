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
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The ID of the user initiating the friendship
            $table->unsignedBigInteger('friend_id'); // The ID of the user being added as a friend
            $table->enum('status', ['pending', 'accepted', 'blocked'])->default('pending'); // Friendship status
            $table->timestamps();
        
            // Define foreign keys
            $table->foreign('user_id')->references('UserID')->on('users')->onDelete('cascade');
            $table->foreign('friend_id')->references('UserID')->on('users')->onDelete('cascade');
        
            // Add unique constraint to ensure mutual friendships are unique
            $table->unique(['user_id', 'friend_id']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
