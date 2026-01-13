<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->uuid('undo_token')->nullable()->after('status');
            $table->timestamp('undo_used_at')->nullable()->after('undo_token');
            $table->index('undo_token');
        });
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropIndex(['undo_token']);
            $table->dropColumn(['undo_token', 'undo_used_at']);
        });
    }
};
