<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Invoicebatch;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\ProcessInvoiceBatch;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;

    }

    public function generate(Request $request)
    {
        try{
            $batchSize = $request->input('batch_size', 100);
            $testMode = $request->boolean('test_mode', 'false');

            $batch = InvoiceBatch::create([
                'total_invoices' => 0,
                'processed_invoices' => 0,
                'failed_invoices' => 0,
                'status' => 'pending',
                'started_at' => now()
            ]);

            $totalInvoices = $this->invoiceService->createInvoiceBatches($batch->id, $batchSize, $testMode);

            $batch->update(['total_invoices'=> $totalInvoices, 'status'=> 'processing']);

            Log::info("Invoice batch {$batch->id} started with {$totalInvoices} invoices");

            return response()->json(['status'=> 'success',
            'message' => 'Invoice generation started',
            'batch_id' => $batch->id,
            'total_invoices' => $totalInvoices]);
        }
        catch(\Exception $e)
        {
            Log::error('Invoice generation failed: ' .$e->getMessage());

            return response()->json(['status'=> 'error','message' => 'Failed to start the invoice generation']);
        }
    }

    public function status($batchId)
    {
        $batch = InvoiceBatch::findOrFail($batchId);

        return response()->json(['batch_id'=> $batch->id,
        'status'=>$batch->status,
        'total_invoices'=> $batch->total_invoices,
        'processed_invoices' => $batch->processed_invoices,
        'failed_invoices' =>$batch->failed_invoices,
        'progress_percentage' => $batch->total_invoices > 0 ? round(($batch->processed_invoices / $batch->total_invoices) * 100, 2): 0,
        'started_at'=> $batch->started_at,
        'completed_at'=> $batch->completed_at]);

    }
}
