<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    public $timestamps = false;

    protected $table = 'transfers';

    protected $fillable = ['reference', 'amount', 'currency', 'destination', 'status'];
}
