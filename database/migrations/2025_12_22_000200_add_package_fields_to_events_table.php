<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackageFieldsToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_type')->default('wedding')->after('id');
            $table->foreignId('event_package_id')->nullable()->after('user_id')->constrained('event_packages')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('status');
            $table->json('feature_overrides')->nullable()->after('rsvp_deadline'); // per-event toggles beyond package
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_type', 'published_at', 'feature_overrides']);
            $table->dropConstrainedForeignId('event_package_id');
        });
    }
}
