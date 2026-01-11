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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('report_categories')->cascadeOnDelete();
            $table->date('activity_date');
            
            // Catkin Fields
            $table->string('reference_source')->nullable(); // Dasar Pelaksanaan
            $table->text('description'); // Uraian Activities
            $table->string('output_result')->nullable(); // Hasil Pekerjaan
            
            // Labul Fields
            $table->text('evidence_link')->nullable(); // Google Drive Link
            
            // Teaching Specific Fields (Nullable)
            $table->string('class_name')->nullable(); // e.g. 7E
            $table->integer('period_start')->nullable(); // Jam ke-
            $table->integer('period_end')->nullable(); // Sampai jam ke-
            $table->text('topic')->nullable(); // Materi
            $table->text('student_outcome')->nullable(); // Ketuntasan/Hasil Siswa

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
