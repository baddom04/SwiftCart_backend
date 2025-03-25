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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->char('zip_code', 4);
            $table->string('city');
            $table->string('street');
            $table->string('detail');
            $table->unsignedBigInteger('store_id')->unique();
            $table->timestamps();

            $table->unique(['country', 'zip_code', 'city', 'street', 'detail']);
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
