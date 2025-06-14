<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Invoice</title>
</head>

<body>
    <p>Hello {{ $customer->name }}</p>
    <p>Your Invoice for {{number_format($invoice->amount, 2)}} is attached.</p>
    <p>Invoice Id: {{$invoice->id}}</p>
    <p>Amount: {{number_format($invoice->amount,2)}}</p>
    <p>Date: {{$invoice->created_at->format('d M Y')}}</p>

</body>

</html>