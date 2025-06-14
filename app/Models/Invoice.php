<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

   protected $fillable = ['customer_id', 'amount', 'pdf','status','email_sent_at','batch_id','attempts','last_error'];

   protected $casts = ['email_sent_at'=> 'datetime', 'amount'=> 'decimal:2'];

   
   public function customer()
   {
    return $this->belongsTo(Customer::class);
   }
}
