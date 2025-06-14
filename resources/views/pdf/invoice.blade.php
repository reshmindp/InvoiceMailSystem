<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            line-height: 1.4; 
            color: #333;
        }
        
        .invoice-header { 
            border-bottom: 2px solid #007bff; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        
        .invoice-title { 
            font-size: 24px; 
            font-weight: bold; 
            color: #007bff; 
        }
        
        .invoice-details { 
            margin-bottom: 30px; 
        }
        
        .customer-info { 
            background-color: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
        }
        
        .amount { 
            font-size: 18px; 
            font-weight: bold; 
            color: #28a745; 
        }
        
        .footer { 
            margin-top: 50px; 
            text-align: center; 
            font-size: 10px; 
            color: #6c757d; 
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1 class="invoice-title">INVOICE #{{ $invoice->id }}</h1>
        <p>Date: {{ $invoice->created_at->format('F d, Y') }}</p>
    </div>

    <div class="invoice-details">
        <div class="customer-info">
            <h3>Bill To:</h3>
            <p><strong>{{ $customer->name }}</strong></p>
            <p>{{ $customer->email }}</p>
        </div>
    </div>

    <div class="amount-section">
        <p>Amount Due: <span class="amount">${{ number_format($invoice->amount, 2) }}</span></p>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated automatically on {{ now()->format('F d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>