<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
        .receipt-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; background: #fff; border-radius: 12px; }
        .receipt-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .receipt-box table td { padding: 12px; vertical-align: top; }
        .receipt-box table tr td:nth-child(2) { text-align: right; }
        .receipt-box table tr.top table td { padding-bottom: 20px; }
        .receipt-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #10b981; font-weight: bold; }
        .receipt-box table tr.information table td { padding-bottom: 40px; }
        .receipt-box table tr.heading td { background: #f8f8f8; border-bottom: 1px solid #ddd; font-weight: bold; }
        .receipt-box table tr.details td { padding-bottom: 20px; }
        .receipt-box table tr.item td { border-bottom: 1px solid #eee; }
        .receipt-box table tr.item.last td { border-bottom: none; }
        .receipt-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; font-size: 24px; color: #10b981; }
        .footer { margin-top: 40px; text-align: center; color: #777; font-size: 14px; }
    </style>
</head>
<body>
    <div class="receipt-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">MOVAM</td>
                            <td>
                                Receipt #: {{ $order->order_number }}<br>
                                Created: {{ $order->created_at->format('M d, Y H:i') }}<br>
                                Payment: {{ strtoupper($order->payment_method) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>Merchant:</strong><br>
                                {{ $order->merchant->business_name }}<br>
                                {{ $order->merchant->address }}
                            </td>
                            <td>
                                <strong>Customer:</strong><br>
                                {{ $order->customer->name }}<br>
                                {{ $order->delivery_address }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>
            @foreach($order->items as $item)
            <tr class="item">
                <td>{{ $item->quantity }}x {{ $item->product->name }}</td>
                <td>₦{{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td></td>
                <td>Total: ₦{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
        <div class="footer">
            Thank you for choosing Movam Logistics!
        </div>
    </div>
</body>
</html>
