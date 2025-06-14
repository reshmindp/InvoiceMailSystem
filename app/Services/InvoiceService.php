<?php

namespace App\Services;
use App\Models\Customer;
use App\Models\Invoice;
use App\Jobs\ProcessInvoiceBatch;
use Illuminate\Support\Facades\DB;

class InvoiceService
{

    public function createInvoiceBatches(int $batchId, int $batchSize = 100, bool $testMode = false):int
    {
        $query = Customer::select('id','email');

        if($testMode)
        {
            $query->limit(50);
        }

        $totalCustomers = $query->count();
        $totalInvoices = 0;

        $query->chunk($batchSize, function($customers) use ($batchId, $totalInvoices){
            $invoices = [];

            foreach($customers as $customer)
            {
                $invoices[] = [
                    'customer_id' => $customer->id,
                    'amount'=>rand(100,999),
                    'status'=> 'pending',
                    'batch_id' => $batchId,
                    'attempts' => 0,
                    'created_at' => now(),
                    'updated_at'=> now()
                ];
            }

            Invoice::insert($invoices);

            $totalInvoices += count($invoices);

            ProcessInvoiceBatch::dispatch($batchId, $customers->pluck('id')->toArray())->onQueue('invoice-processing');
        });

        return $totalInvoices;

    }
    
    
}
