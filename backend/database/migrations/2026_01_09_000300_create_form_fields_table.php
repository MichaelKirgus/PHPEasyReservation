<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->unsignedInteger('min_length')->nullable();
            $table->unsignedInteger('max_length')->nullable();
            $table->string('pattern')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('visible_public')->default(true);
            $table->boolean('visible_admin')->default(true);
            $table->boolean('is_email')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
