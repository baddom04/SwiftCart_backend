<?php

use App\Models\Grocery;
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
        Schema::create('groceries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->integer('quantity')->nullable();
            $table->enum('unit', Grocery::getUnitTypes())->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('household_id')->references('id')->on('households')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groceries');
    }
};
