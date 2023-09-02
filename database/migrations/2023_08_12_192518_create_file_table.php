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
        Schema::create('files', function (Blueprint $table) {
            $table->id('FileID');
            $table->string('FileName');
            $table->unsignedDecimal('FileSize');
            $table->string('FileType');
            $table->string('FileContent');
            $table->timestamp('Timestamp')->useCurrent();
            $table->unsignedBigInteger('SenderID');
            $table->unsignedBigInteger('RecipientID');
            $table->foreign('SenderID')->references('UserID')->on('users');
            $table->foreign('RecipientID')->references('UserID')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
