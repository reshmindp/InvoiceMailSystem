<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Dompdf\Dompdf;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class GenerateInvoicePdf implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $retryAfter = 30;

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
            $startTime = microtime(true);

            
            $this->invoice->load('customer');

             
            $pdf = $this->generateOptimizedPdf();
            
            $filename = "invoice_{$this->invoice->id}.pdf";
            $path = "invoices/{$filename}";

             
            Storage::disk('public')->put($path, $pdf->output());

            $this->invoice->update([
                'pdf' => $path,
                'status' => 'pdf_generated'
            ]);

            $generationTime = (microtime(true) - $startTime) * 1000;  
            
            if ($generationTime > 500) {
                Log::warning("PDF generation took {$generationTime}ms for invoice {$this->invoice->id}");
            }

            
            SendInvoiceEmail::dispatch($this->invoice)
                ->onQueue('email-sending')
                ->delay(now()->addSeconds(rand(1, 5))); 

        } catch (\Exception $e) {
            Log::error("PDF generation failed for invoice {$this->invoice->id}: {$e->getMessage()}");
            
            $this->invoice->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'attempts' => $this->invoice->attempts + 1
            ]);

            throw $e;
        }
    }

    private function generateOptimizedPdf()
    {
         
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
            'customer' => $this->invoice->customer
        ]);

         
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultFont' => 'Arial',
            'dpi' => 72,  
        ]);

        return $pdf;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateInvoicePdf failed for invoice {$this->invoice->id}: {$exception->getMessage()}");
        
        $this->invoice->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
            'attempts' => $this->invoice->attempts + 1
        ]);
    }
}
