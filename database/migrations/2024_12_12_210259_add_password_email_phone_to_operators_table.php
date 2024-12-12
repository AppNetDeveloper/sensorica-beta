<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPasswordEmailPhoneToOperatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('password')->nullable()->after('name'); // Campo password, puede ser nulo
            $table->string('email')->nullable()->after('password');  // Campo email, puede ser nulo
            $table->string('phone')->nullable()->after('email');     // Campo phone, puede ser nulo
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['password', 'email', 'phone']);
        });
    }
}

