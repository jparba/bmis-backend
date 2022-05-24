<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('brgy_id')->nullable();
            $table->string('firstname');
            $table->string('middlename');
            $table->string('lastname');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('dob');
            $table->string('pob');
            $table->string('gender');
            $table->string('weight')->nullable();
            $table->string('height')->nullable();
            $table->string('religion');
            $table->string('blood_type');
            $table->string('occupation')->nullable();
            $table->string('household')->nullable();
            $table->string('civil_status');
            $table->string('spouse_name')->nullable();
            $table->string('current_address');
            $table->string('pernament_address');
            $table->string('tin')->nullable();
            $table->string('pagibig')->nullable();
            $table->string('sss')->nullable();
            $table->string('philhealth')->nullable();
            $table->string('pic')->nullable();
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
        Schema::dropIfExists('residents');
    }
}
