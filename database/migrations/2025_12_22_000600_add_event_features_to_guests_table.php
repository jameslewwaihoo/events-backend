<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventFeaturesToGuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('invite_status')->nullable()->after('status'); // pending|sent|bounced
            $table->string('rsvp_status')->default('pending')->after('invite_status'); // pending|yes|no|maybe
            $table->unsignedSmallInteger('party_size')->default(1)->after('rsvp_status');
            $table->string('meal_choice')->nullable()->after('party_size');
            $table->string('check_in_status')->nullable()->after('meal_choice');
            $table->timestamp('check_in_at')->nullable()->after('check_in_status');
            $table->foreignId('seating_table_id')->nullable()->after('check_in_at')->constrained('seating_tables')->nullOnDelete();
            $table->string('seat_label')->nullable()->after('seating_table_id');

            $table->index(['event_id', 'rsvp_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex('guests_event_id_rsvp_status_index');
            $table->dropConstrainedForeignId('seating_table_id');
            $table->dropColumn([
                'invite_status',
                'rsvp_status',
                'party_size',
                'meal_choice',
                'check_in_status',
                'check_in_at',
                'seat_label',
            ]);
        });
    }
}
