<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rhk_label',
        'is_teaching',
    ];

    protected $casts = [
        'is_teaching' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class, 'category_id');
    }
}
