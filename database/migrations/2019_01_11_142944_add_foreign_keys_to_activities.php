<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\Account\Account;
use App\Models\Contact\Activity;

class AddForeignKeysToActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the activities table to make sure that we don't have
        // "ghost" activities that are not associated with any account
        Activity::chunk(200, function ($activities) {
            foreach ($activities as $activity) {
                try {
                    Account::findOrFail($activity->account_id);
                } catch (ModelNotFoundException $e) {
                    $activity->delete();
                    continue;
                }
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('activity_type_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('set null');
        });

        $activityContacts = DB::table('activity_contact')->get();
        foreach ($activityContacts as $activityContact) {
            try {
                Account::findOrFail($activityContact->account_id);
            } catch (ModelNotFoundException $e) {
                DB::table('activity_contact')->where('id', $activityContact->id)->delete();
                continue;
            }
        }

        Schema::table('activity_contact', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}