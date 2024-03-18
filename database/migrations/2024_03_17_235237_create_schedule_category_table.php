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
        Schema::table('schedule_category', function (Blueprint $table) {
            $table->integer('scheduleId')->index('scheduleId');
            $table->integer('categoryId')->index('categoryId');
            $table->primary(['categoryId', 'categoryId']);
            $table->engine = 'MyISAM';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_category');
    }
};
