<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHalalMealToRsvps extends Migration
{
    public function up()
    {
        Schema::table('rsvps', function (Blueprint $table) {
            $table->boolean('halal_meal')->nullable();
        });
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE rsvps DROP COLUMN halal_meal');
            return;
        }

        Schema::table('rsvps', function (Blueprint $table) {
            $table->dropColumn('halal_meal');
        });
    }
}
