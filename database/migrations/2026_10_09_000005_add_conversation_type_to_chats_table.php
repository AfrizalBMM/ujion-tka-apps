<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add conversation_type to chats to enforce guru-admin only chat.
        // guru_admin = chat between guru and superadmin (the only allowed type).
        Schema::table('chats', function (Blueprint $table) {
            $table->string('conversation_type', 30)->default('guru_admin')->after('to_user_id');
            $table->index('conversation_type');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex(['conversation_type']);
            $table->dropColumn('conversation_type');
        });
    }
};
