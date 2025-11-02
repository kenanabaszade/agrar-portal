<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('message_translations')->nullable()->after('message');
        });

        \DB::table('notifications')->get()->each(function ($notification) {
            $updates = [];
            if (!empty($notification->title)) {
                $updates['title_translations'] = json_encode(['az' => $notification->title], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($notification->message)) {
                $updates['message_translations'] = json_encode(['az' => $notification->message], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($updates)) {
                \DB::table('notifications')->where('id', $notification->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE notifications DROP COLUMN title, DROP COLUMN message');
        \DB::statement('ALTER TABLE notifications RENAME COLUMN title_translations TO title');
        \DB::statement('ALTER TABLE notifications RENAME COLUMN message_translations TO message');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE notifications RENAME COLUMN title TO title_translations');
        \DB::statement('ALTER TABLE notifications RENAME COLUMN message TO message_translations');

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('title')->after('type');
            $table->text('message')->after('title');
        });

        \DB::table('notifications')->get()->each(function ($notification) {
            $updates = [];
            if (!empty($notification->title_translations)) {
                $data = json_decode($notification->title_translations, true);
                if (is_array($data)) $updates['title'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($notification->message_translations)) {
                $data = json_decode($notification->message_translations, true);
                if (is_array($data)) $updates['message'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($updates)) {
                \DB::table('notifications')->where('id', $notification->id)->update($updates);
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'message_translations']);
        });
    }
};
