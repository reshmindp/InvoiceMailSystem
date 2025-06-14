<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;


class ProcessInvoiceBatch implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Batchable;

    public $tries = 3;
    public $timeout = 300;
    public $retryAfter = 60;
    
    private int $invoiceBatchId;
    private array $customerIds;
    
    /**
     * Create a new job instance.
     */
    public function __construct(int $batchId, array $customerIds)
    {
        $this->invoiceBatchId = $batchId;
        $this->customerIds = $customerIds;

        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->batch()?->cancelled())
        {
            return;
        }

        try{
            $invoices = Invoice::with('customer')->where('batch_id', $this->invoiceBatchId)->whereIn('customer_id', $this->customerIds)
            ->where('status','pending')->get();
            
            foreach($invoices as $invoice)
            {
                if($this->batch()?->cancelled())
                {
                    break;
                }

                try{
                    GenerateInvoicePdf::dispatch($invoice)
                        ->onQueue('pdf-generation')
                        ->delay(now()->addMilliseconds(100));

                }
                catch(\Exception $e)
                {
                    Log::error('Failed to dispatch PDF generation for the invoice {$invoice->id}: {$e->getMessage()}');
                    $invoice->update([
                        'status' => 'failed',
                        'last_error' => $e->getMessage(),
                        'attempts' => $invoice->attempts + 1
                    ]);
                }
            }

        }
        catch(\Exception $e)
        {
            Log::error('Batch processing failed for batch {$this->invoiceBatchId}: {$e->getMessage()}');
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessInvoiceBatch failed for batch {$this->invoiceBatchId}: {$exception->getMessage()}");
        
        InvoiceBatch::where('id', $this->invoiceBatchId)->update([
            'status' => 'failed',
            'error_details' => ['message' => $exception->getMessage()]
        ]);
    }
}
