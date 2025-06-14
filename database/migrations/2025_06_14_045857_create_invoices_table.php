<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount',10,2);
            $table->string('pdf')->nullable();
            $table->enum('status', ['pending', 'pdf_generated', 'completed','failed','email_failed','permanently_failed'])->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->index(['batch_id', 'status']);
            $table->index(['status', 'attempts']);
            $table->foreign('batch_id')->references('id')->on('invoice_batches')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
