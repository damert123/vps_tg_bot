<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_monitorings', function (Blueprint $table) {
            $table->id();
            $table->integer('server_id');
            $table->string('last_cpu_usage')->nullable();
            $table->string('last_ram_usage')->nullable();
            $table->string('last_hdd_usage')->nullable();
            $table->timestamp('last_update');
            $table->string('ssh_connection', 50);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_monitorings');
    }
};
