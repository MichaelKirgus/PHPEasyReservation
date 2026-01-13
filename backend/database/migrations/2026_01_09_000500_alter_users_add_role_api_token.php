<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin');
            $table->string('api_token', 255)->nullable()->unique();
            $table->boolean('api_token_is_hashed')->default(false);
            $table->boolean('active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'api_token', 'api_token_is_hashed', 'active']);
        });
    }
};
