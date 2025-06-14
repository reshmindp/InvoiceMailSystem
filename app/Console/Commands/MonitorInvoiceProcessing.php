<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\InvoiceBatch;
use Illuminate\Support\Facades\Log;



class MonitorInvoiceProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:monitor {batch_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Invoice processing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchId =$this->argument('batch_id');

        if($batchId)
        {
            $this->monitorSpecificBatch($batchId);
        }
        else
        {
            $this->monitorAllActiveBatches();
        }
    }

    private function monitorSpecificBatch($batchId)
    {
        $batch = InvoiceBatch::find($batchId);

        if(!$batch)
        {
            $this->error('Batch {$batchId} not found');
            return;
        }

        $this->displayBatchStatus($batch);

    }

    private function monitorAllActiveBatches()
    {
        $batches = InvoiceBatch::whereIn('status',['pending', 'processing'])->get();

        foreach($batches as $batch)
        {
            $this->displayBatchStatus($batch);


        }
    }

    private function displayBatchStatus($batch)
    {
        $progress = $batch->total_invoices > 0 ? round(($batch->processed_invoices / $batch->total_invoices) * 100, 2): 0;

        $this->info("Batch {$batch->id}: ");
        $this->line("Status: {$batch->status}");
        $this->line("Progress: {$progress}% ({$batch->processed_invoices} / {$batch->total_invoices})");
        $this->line("Failed: {$batch->failed_invoices}");
        $this->line("Started: {$batch->started_at}");
        $this->newLine();

    }
}
