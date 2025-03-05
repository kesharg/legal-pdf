<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localization extends Model
{
    use HasFactory;

    protected $guarded = [];


    public $timestamps = false;

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
