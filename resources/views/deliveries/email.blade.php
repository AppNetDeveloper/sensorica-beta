<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Delivery Note') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .content h2 {
            color: #667eea;
            font-size: 18px;
            margin-top: 0;
        }
        .order-list {
            list-style: none;
            padding: 0;
        }
        .order-list li {
            padding: 10px;
            background: white;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $mode === 'single' ? __('Delivery Note') : __('All Delivery Notes') }}</h1>
        <p>{{ __('Date') }}: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="content">
        <h2>{{ __('Hello') }}!</h2>
        <p>{{ __('Attached you will find') }} {{ $mode === 'single' ? __('the delivery note') : __('all delivery notes') }} {{ __('for the following orders') }}:</p>

        <ul class="order-list">
            @foreach($orders as $order)
                <li>
                    <strong>{{ $order->order_id }}</strong><br>
                    <span style="color: #666;">{{ __('Client') }}: {{ $order->customerClient->name ?? 'N/A' }}</span>
                </li>
            @endforeach
        </ul>

        <p>{{ __('The PDF document is attached to this email for your review') }}.</p>
    </div>

    <div class="footer">
        <p>{{ config('app.name') }}</p>
        <p>{{ __('This is an automated email, please do not reply') }}.</p>
    </div>
</body>
</html>
