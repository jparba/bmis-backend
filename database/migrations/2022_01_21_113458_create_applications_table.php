<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('type')->comment('1:Barangay certificate; 2:Barangay certificate with cedula; 3:Cedula; 4:Certificate of Indigency; 5:Barangay clearance; 6:Barangay clearance with cedula;');
            $table->integer('status')->comment('1:pending; 2:approved; 3:rejected, 4:cancelled');
            $table->longText('attachment')->nullable();
            $table->text('note')->nullable();
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
        Schema::dropIfExists('applications');
    }
}
