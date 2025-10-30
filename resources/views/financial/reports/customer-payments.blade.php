<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Payment History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .report-period {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .amount {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge.outstanding {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        .badge.current {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="report-title">Customer Payment History</div>
    <div class="report-period">Period: {{ $data['period']['start'] }} - {{ $data['period']['end'] }}</div>

    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Contact</th>
                <th>Transactions</th>
                <th>Invoices</th>
                <th class="amount">Total Paid</th>
                <th class="amount">Total Invoiced</th>
                <th class="amount">Outstanding</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['customers'] as $customer)
                <tr>
                    <td>{{ $customer['name'] }}</td>
                    <td>
                        {{ $customer['email'] }}<br>
                        <small>{{ $customer['phone'] }}</small>
                    </td>
                    <td>{{ $customer['transactions_count'] }}</td>
                    <td>{{ $customer['invoices_count'] }}</td>
                    <td class="amount">{{ $customer['formatted_paid'] }}</td>
                    <td class="amount">{{ $customer['formatted_invoiced'] }}</td>
                    <td class="amount">{{ $customer['formatted_outstanding'] }}</td>
                    <td>
                        <span class="badge {{ $customer['payment_status'] }}">
                            {{ ucfirst($customer['payment_status']) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

