<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use HasFactory, Billable;

    protected $fillable = ['name', 'subdomain', 'plan'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

     /**
     * Convenience helper for Blade/Vue: is this tenant currently on a paid,
     * active subscription (the "default" subscription name used throughout
     * this app)?
     */
    public function isSubscribed(): bool
    {
        return $this->subscribed('default');
    }
}