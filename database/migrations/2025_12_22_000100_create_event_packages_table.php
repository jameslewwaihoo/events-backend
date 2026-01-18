<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEventPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // basic | mid | premium
            $table->unsignedInteger('price_cents')->default(0);

            $table->boolean('rsvp_enabled')->default(true);
            $table->boolean('photos_enabled')->default(true);
            $table->boolean('video_enabled')->default(false);
            $table->boolean('sessions_enabled')->default(false);
            $table->boolean('email_invites_enabled')->default(false);
            $table->boolean('attendance_enabled')->default(false);
            $table->boolean('meals_enabled')->default(false);
            $table->boolean('seating_enabled')->default(false);

            $table->unsignedInteger('max_guests')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        DB::table('event_packages')->insert([
            [
                'name' => 'Basic',
                'code' => 'basic',
                'price_cents' => 0,
                'rsvp_enabled' => true,
                'photos_enabled' => true,
                'video_enabled' => false,
                'sessions_enabled' => false,
                'email_invites_enabled' => false,
                'attendance_enabled' => false,
                'meals_enabled' => false,
                'seating_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plus',
                'code' => 'mid',
                'price_cents' => 5000,
                'rsvp_enabled' => true,
                'photos_enabled' => true,
                'video_enabled' => true,
                'sessions_enabled' => true,
                'email_invites_enabled' => true,
                'attendance_enabled' => false,
                'meals_enabled' => false,
                'seating_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'code' => 'premium',
                'price_cents' => 10000,
                'rsvp_enabled' => true,
                'photos_enabled' => true,
                'video_enabled' => true,
                'sessions_enabled' => true,
                'email_invites_enabled' => true,
                'attendance_enabled' => true,
                'meals_enabled' => true,
                'seating_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_packages');
    }
}
