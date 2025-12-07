<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    /** @use HasFactory<\Database\Factories\AssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'center_id',
        'group_id',
        'lesson_id',
        'title',
        'max_score',
        'scheduled_at',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function results()
    {
        return $this->hasMany(AssessmentResult::class);
    }
}
