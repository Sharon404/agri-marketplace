<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('type');
            $table->string('action_url')->nullable()->after('message');
            $table->json('metadata')->nullable()->after('action_url');
            $table->timestamp('expires_at')->nullable()->after('read_at');
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['priority', 'action_url', 'metadata', 'expires_at']);
        });
    }
};
