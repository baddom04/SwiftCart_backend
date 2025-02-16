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
            $table->string('name');
            $table->integer('quantity')->nullable();
            $table->enum('unit', Grocery::getUnitTypes())->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('household_id');
            $table->timestamps();

            $table->foreign('household_id')->references('id')->on('households')->onDelete('cascade');
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
