<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'activity_date',
        'reference_source',
        'description',
        'output_result',
        'evidence_link',
        'class_name',
        'period_start',
        'period_end',
        'topic',
        'student_outcome',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ReportCategory::class, 'category_id');
    }
}
