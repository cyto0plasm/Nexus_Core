<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopUser extends Model
{
    protected $table = 'shop_user';

    protected $fillable = [
        'shop_id',
        'user_id',
        'role',
        'is_active',
    ];

     protected $casts = [
        'is_active' => 'boolean',
    ];

     public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

     public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
