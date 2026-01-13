<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $defaults = [
            ['name' => 'admin_reservation_notify_default', 'value' => '0'],
            ['name' => 'moderator_reservation_notify_default', 'value' => '0'],
        ];

        foreach ($defaults as $item) {
            DB::table('settings')->updateOrInsert(['name' => $item['name']], ['value' => $item['value']]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'admin_reservation_notify_default',
            'moderator_reservation_notify_default',
        ])->delete();
    }
};
