<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('privacy_policy_enabled')->default(false);
            $table->text('privacy_policy_text')->nullable();
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['privacy_policy_enabled', 'privacy_policy_text']);
        });
    }
};
