<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Jobs\GenerateInvoicePdf;

class RetryFailedInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:retry {batch_id} {--max-attempts=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed invoice';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchId = $this->argument('batch_id');
        $maxAttempts = $this->option('max-attempts');
        $query = Invoice::where('status', 'failed')->where('attempts', '<', $maxAttempts);

        if($batchId)
        {
            $query->where('batch_id', $batchId);
        }

        $failedInvoices = $query->get();

        if($failedInvoices->isEmpty())
        {
            $this->info('No failed invoices for retry!');

            return;
        }

        $this->info('Retrying {$failedInvoices->count()} failed invoices');

        foreach($failedInvoices as $invoice)
        {
            $invoice->update(['status'=> 'pending']);
            GenerateInvoicePdf::dispatch($invoice)->inQueue('pdf-generation');
        }

        $this->info('Retry jobs dispatched success');
    }
}
