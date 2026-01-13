<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $defaults = [
            ['name' => 'email_reservation_success_template_id', 'value' => '0'],
            ['name' => 'email_reservation_cancel_template_id', 'value' => '0'],
            ['name' => 'email_waitlist_validation_success_template_id', 'value' => '0'],
            ['name' => 'email_waitlist_promoted_template_id', 'value' => '0'],
            ['name' => 'email_waitlist_cancel_template_id', 'value' => '0'],
        ];

        foreach ($defaults as $item) {
            DB::table('settings')->updateOrInsert(['name' => $item['name']], ['value' => $item['value']]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'email_reservation_success_template_id',
            'email_reservation_cancel_template_id',
            'email_waitlist_validation_success_template_id',
            'email_waitlist_promoted_template_id',
            'email_waitlist_cancel_template_id',
        ])->delete();
    }
};
