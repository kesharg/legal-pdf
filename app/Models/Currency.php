<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    protected $table = 'currencies';
    protected $fillable = [
        'currency', 'code', 'minor_unit', 'symbol'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class); // Assuming 'currency_id' is the foreign key
    }
}
