<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('name');
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('invite_code')->nullable()->index(); // future private RSVP
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['event_id', 'phone']);
            $table->index(['event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guests');
    }
}
