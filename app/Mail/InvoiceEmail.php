<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;



class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Email',
            to: [$this->invoice->customer->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.invoice',
            with: ['invoice' => $this->invoice, 'customer'=> $this->invoice->customer]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if($this->invoice->pdf && $this->pdfExists($this->invoice->pdf))
        {
            $attachments[] = Attachment::fromPath($this->invoice->pdf)->as('Invoice_'. $this->invoice->id.'.pdf')
            ->withMime('application/pdf');
        }
        
        return $attachments;
    }

    private function pdfExists(string $pdfPath): bool
    {
        if(Storage::disk('public')->exists($pdfPath))
        {
            return true;
        }

        if(file_exists($pdfPath))
        {
            return true;
        }

        if(file_exists(storage_path('app/'. $pdfPath)))
        {
            return true;
        }
        return false;
    }
}
