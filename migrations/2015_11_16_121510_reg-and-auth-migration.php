<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RegAndAuthMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function($table) {
                $table->string('username', 100)->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'activated')) {
            Schema::table('users', function($table) {
                $table->boolean('activated')->default(false);
            });
            DB::statement("UPDATE users SET activated = 1");
        }

        if (!Schema::hasColumn('users', 'activation_code')) {
            Schema::table('users', function($table) {
                $table->string('activation_code', 100)->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'reset_password_code')) {
            Schema::table('users', function($table) {
                $table->string('reset_password_code', 100)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
