<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPrivateRsvps extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('private_rsvp')->default(false);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->unique();
        });

        Schema::table('rsvps', function (Blueprint $table) {
            $table->unsignedSmallInteger('confirmed_seats')->nullable();
            $table->boolean('household_reply')->nullable();
            $table->unique(['guest_id', 'household_reply']);
        });
    }

    public function down()
    {
        Schema::table('rsvps', function (Blueprint $table) {
            $table->dropUnique(['guest_id', 'household_reply']);
        });
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['invitation_token']);
        });

        // Laravel 8 requires DBAL for SQLite column drops. SQLite 3.35+ can do them directly.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE rsvps DROP COLUMN household_reply');
            DB::statement('ALTER TABLE rsvps DROP COLUMN confirmed_seats');
            DB::statement('ALTER TABLE guests DROP COLUMN invitation_token');
            DB::statement('ALTER TABLE events DROP COLUMN private_rsvp');
            return;
        }

        Schema::table('rsvps', function (Blueprint $table) {
            $table->dropColumn(['household_reply', 'confirmed_seats']);
        });
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('invitation_token');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('private_rsvp');
        });
    }
}
