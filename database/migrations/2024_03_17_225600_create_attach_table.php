<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attach', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->integer('template_id');
            $table->timestamps();
        });

        Schema::table('attach', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attach');
    }
};
