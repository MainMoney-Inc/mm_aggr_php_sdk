<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public $timestamps = false;

    protected $table = 'orders';

    protected $fillable = ['reference', 'product_id', 'amount', 'currency', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
