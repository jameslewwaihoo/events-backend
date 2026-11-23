<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddEndAtToEventSessions extends Migration
{
    public function up()
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dateTime('end_at')->nullable();
        });
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE event_sessions DROP COLUMN end_at');
            return;
        }

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropColumn('end_at');
        });
    }
}
