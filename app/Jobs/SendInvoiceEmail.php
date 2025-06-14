<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Invoice;
use App\Models\InvoiceBatch;
use App\Mail\InvoiceEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SendInvoiceEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $timeout = 60;
    public $retryAfter = 120;

    private Invoice $invoice;

    /**
     * Create a new job instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         try {
            
            $rateLimitKey = 'email_rate_limit';
            
            if (!RateLimiter::attempt($rateLimitKey, 50, function() {
                return $this->sendEmail();
            }, 60)) {
            
                $this->release(30);
                return;
            }

        } catch (\Exception $e) {
            Log::error("Email sending failed for invoice {$this->invoice->id}: {$e->getMessage()}");
            
            $this->invoice->update([
                'status' => 'email_failed',
                'last_error' => $e->getMessage(),
                'attempts' => $this->invoice->attempts + 1
            ]);

            $delay = min(pow(2, $this->invoice->attempts) * 60, 3600);  
            $this->release($delay);
        }
    }

    private function sendEmail(): bool
    {
        $this->invoice->load('customer');

        Mail::to($this->invoice->customer->email)
            ->send(new InvoiceEmail($this->invoice));

        $this->invoice->update([
            'status' => 'completed',
            'email_sent_at' => now()
        ]);

        
        $this->updateBatchProgress();

        Log::info("Invoice {$this->invoice->id} emailed successfully to {$this->invoice->customer->email}");
        
        return true;
    }

    private function updateBatchProgress(): void
    {
        $batch = InvoiceBatch::find($this->invoice->batch_id);
        
        if ($batch) {
            $batch->increment('processed_invoices');
            
            
            if ($batch->processed_invoices >= $batch->total_invoices) {
                $batch->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                
                Log::info("Batch {$batch->id} completed successfully");
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendInvoiceEmail permanently failed for invoice {$this->invoice->id}: {$exception->getMessage()}");
        
        $this->invoice->update([
            'status' => 'permanently_failed',
            'last_error' => $exception->getMessage(),
            'attempts' => $this->invoice->attempts + 1
        ]);

    
        $batch = InvoiceBatch::find($this->invoice->batch_id);
        if ($batch) {
            $batch->increment('failed_invoices');
        }
    }
}
