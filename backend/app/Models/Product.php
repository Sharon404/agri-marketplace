<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit', // kg, ton, etc.
        'description',
    ];

    public function farmerListings()
    {
        return $this->hasMany(FarmerListing::class);
    }

    public function buyerRequests()
    {
        return $this->hasMany(BuyerRequest::class);
    }
}