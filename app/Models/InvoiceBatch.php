<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceBatch extends Model
{
    protected $fillable = ['total_invoices','processed_invoices','failed_invoices',
    'status','started_at','completed_at','error_details'];

    protected $casts = ['started_at' => 'datetime', 'completed_at'=> 'datetime','error_details'=>'array'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class,'batch_id');
    }
}
