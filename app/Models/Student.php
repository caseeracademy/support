<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // This model doesn't use database, it's just a wrapper for API data
    protected $guarded = [];

    public $incrementing = false;

    /**
     * Create a Student instance from API data
     */
    public static function fromApiData(array $data): self
    {
        $student = new self;
        $student->forceFill($data);
        $student->exists = true; // Mark as existing

        return $student;
    }
}
