<?php
// - who owns it (owner_id → user)
// - all members (via pivot)
// - filter members by role
// - filter active/inactive members
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'owner_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // the user who created/owns this shop
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // all members regardless of role
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user')
                    ->using(ShopUser::class)
                    ->withPivot('role', 'is_active')
                    ->withTimestamps();
    }

    // only admins
    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', User::ROLE_ADMIN);
    }

     // only cashiers
    public function cashiers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', User::ROLE_CASHIER);
    }

     // only active members
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }


}
