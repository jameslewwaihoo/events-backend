<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->default(0);

            $table->string('slug')->unique(); // public key
            $table->string('title');
            $table->date('event_date')->nullable();

            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_map_url')->nullable();

            $table->text('welcome_message')->nullable();
            $table->dateTime('rsvp_deadline')->nullable();

            $table->enum('status', ['draft', 'published'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
