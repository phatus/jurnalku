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
        Schema::table('users', function (Blueprint $table) {
            // Additional Profile Fields
            $table->string('nip')->nullable();
            $table->string('pangkat_gol')->nullable(); // e.g., Penata Muda / III/a
            $table->string('jabatan')->nullable(); // e.g., Guru Ahli Pertama
            $table->string('unit_kerja')->nullable();
            
            // Headmaster Info for signatures
            $table->string('headmaster_name')->nullable();
            $table->string('headmaster_nip')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nip', 'pangkat_gol', 'jabatan', 'unit_kerja', 
                'headmaster_name', 'headmaster_nip'
            ]);
        });
    }
};
