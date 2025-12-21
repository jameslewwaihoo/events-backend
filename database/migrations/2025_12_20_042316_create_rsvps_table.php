<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRsvpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('event_sessions')->nullOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->enum('attendance_status', ['attending', 'declined']);
            $table->boolean('allow_public_share')->nullable();
            $table->text('remarks')->nullable();

            $table->dateTime('submitted_at')->nullable();

            $table->timestamps();

            $table->index(['event_id', 'phone']);
            $table->index(['event_id', 'email']);
            $table->index(['event_id', 'attendance_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rsvps');
    }
}
