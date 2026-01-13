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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('implementation_basis_id')->nullable()->constrained('implementation_bases')->nullOnDelete();
            // We make reference_source nullable if it wasn't already, though it was created as nullable in previous migration
            
            // We also make class_name nullable if not already, but we might want to keep it until fully migrated
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['implementation_basis_id']);
            $table->dropColumn('implementation_basis_id');
        });
    }
};
