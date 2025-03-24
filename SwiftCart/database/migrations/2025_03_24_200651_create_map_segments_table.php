<?php

use App\Models\MapSegment;
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
        Schema::create('map_segments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('x');
            $table->unsignedInteger('y');
            $table->unsignedBigInteger('map_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->enum('type', MapSegment::getSegmentTypes());

            $table->foreign('map_id')->references('id')->on('maps')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_segments');
    }
};
