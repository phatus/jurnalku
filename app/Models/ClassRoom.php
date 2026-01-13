<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_class_room');
    }
}
