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
        'implementation_basis_id',
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

    public function implementationBasis()
    {
        return $this->belongsTo(ImplementationBasis::class);
    }

    public function classRooms()
    {
        return $this->belongsToMany(ClassRoom::class, 'activity_class_room');
    }
}
