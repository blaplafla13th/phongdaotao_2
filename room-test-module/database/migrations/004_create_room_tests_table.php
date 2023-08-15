<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_tests', function (Blueprint $table) {
            $table->id();
            $table->Integer('room_detail_id');
            $table->Integer('shift_id');
            $table->unique(['room_detail_id', 'shift_id']);
            $table->Integer("quantity");
            $table->Integer('exam_test_id')->nullable();
            $table->Integer('need_supervisor')->default(2);
            $table->Integer('last_edited');
            $table->Integer('supervisor1')->nullable();
            $table->Integer('supervisor2')->nullable();
            $table->Integer('supervisor3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('room_tests');
    }
};
