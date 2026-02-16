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
        Schema::create('ready_sent', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->foreignId('subscriber_id')
                ->constrained('subscribers')
                ->onDelete('cascade');
            $table->string('email');
            $table->foreignId('template_id')
                ->constrained('templates')
                ->onDelete('cascade');
            $table->string('template');
            $table->tinyInteger('success')->default(0);
            $table->text('errorMsg')->nullable();
            $table->tinyInteger('readMail')->nullable();
            $table->foreignId('schedule_id')
                ->constrained('schedule')
                ->onDelete('cascade');
            $table->foreignId('log_id')
                ->constrained('logs')
                ->onDelete('cascade');
            $table->timestamps();
            $table->index(['schedule_id', 'success', 'subscriber_id'], 'idx_rs_sched_success_sub');
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
