<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
   use HasFactory;

   protected $fillable = ['name','email'];

   public function invoives()
   {
    return $this->hasMany(Invoice::class);
   }
}
