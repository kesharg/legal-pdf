<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;
    protected $table = 'countries';
    protected $fillable = [
        "name",
        "code",
        "language_code",
        "currency",
        "flag",
        "is_enable",
        "sub_domain_prefix",

    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function language()
    {
        return $this->hasOne(Language::class, 'code', 'language_code');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function currencies()
    {
        return $this->hasOne(Currency::class, 'code', 'currency');
    }
}
