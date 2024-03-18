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
        Schema::table('ready_sent', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('subscriberId')->index('subscriberId');
            $table->string('email');
            $table->integer('templateId')->index('templateId');
            $table->string('template');
            $table->tinyInteger('success');
            $table->text('errorMsg')->nullable();
            $table->tinyInteger('readMail')->nullable();
            $table->integer('scheduleId')->index('scheduleId');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ready_sent');
    }
};
