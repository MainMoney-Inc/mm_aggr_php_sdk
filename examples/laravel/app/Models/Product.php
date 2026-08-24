<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public $timestamps = false;

    protected $table = 'products';

    protected $fillable = ['sku', 'name', 'description', 'price', 'currency'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
