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
        Schema::create('room_detail_histories', function (Blueprint $table) {
            $table->id();
            $table->Integer('room_detail_id');
            $table->String('name', 5);
            $table->timestamp('created_at')->useCurrent();
            $table->integer('status');
            $table->integer('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('room_detail_histories');
    }
};
