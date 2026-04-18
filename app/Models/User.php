<?php
// - what shops it belongs to (via pivot)
// - what role it has IN a specific shop
// - helper: isAdminOf(shop), isCashierOf(shop)
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email','phone', 'password','trial_ends_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    const ROLE_ADMIN = 'admin';
    const ROLE_CASHIER = 'cashier';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
             'trial_ends_at'        => 'datetime',
            'subscribed_at'        => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }
     public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
     // Returns the currently active subscription if any
     public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }
    public function isSubscribed(): bool
    {
        return $this->activeSubscription() !== null;
    }
     public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
     // Full access = active subscription OR still on trial
    public function hasFullAccess(): bool
    {
        return $this->isSubscribed() || $this->onTrial();
    }

    // shop this user owns
    public function ownedShop(): HasOne
    {
        return $this->hasOne(Shop::class, 'owner_id');
    }

   // all shops this user is a member of
   public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')
                    ->using(ShopUser::class)
                    ->withPivot('role', 'is_active')
                    ->withTimestamps();
    }

    // role helpers — pass the shop to check role in context
    public function isAdminOf(Shop $shop): bool
    {
        return $this->shops()
                    ->wherePivot('shop_id', $shop->id)
                    ->wherePivot('role', self::ROLE_ADMIN)
                    ->exists();
    }
     public function isCashierOf(Shop $shop): bool
    {
        return $this->shops()
                    ->wherePivot('shop_id', $shop->id)
                    ->wherePivot('role', self::ROLE_CASHIER)
                    ->exists();
    }
     public function isActiveIn(Shop $shop): bool
    {
        return $this->shops()
                    ->wherePivot('shop_id', $shop->id)
                    ->wherePivot('is_active', true)
                    ->exists();
    }

}
