<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity_override')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('auto_close_minutes_before')->nullable();
            $table->unsignedInteger('auto_email_template_id')->nullable();
            $table->unsignedInteger('auto_email_offset_minutes_before')->nullable();
            $table->dateTime('auto_email_sent_at')->nullable();
            $table->timestamps();
            $table->index(['active', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
