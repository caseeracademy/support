<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expenses Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
        .filters {
            background-color: #f9f9f9;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 3px solid #dc2626;
            font-size: 10px;
        }
        .summary-box {
            background-color: #fef2f2;
            border: 1px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #dc2626;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            color: #dc2626;
        }
        .total-row {
            background-color: #fee2e2;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #dc2626;
            padding-top: 10px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="report-title">Expenses Report</div>
    <div class="report-period">
        Period: {{ $start_date }} to {{ $end_date }}
    </div>

    @if(!empty($filters['category']) || !empty($filters['payment_method']))
    <div class="filters">
        <strong>Filters Applied:</strong><br>
        @if(!empty($filters['category']))
            Category: {{ $filters['category'] }}<br>
        @endif
        @if(!empty($filters['payment_method']))
            Payment Method: {{ $filters['payment_method'] }}
        @endif
    </div>
    @endif

    <div class="summary-box">
        <div class="summary-row">
            <span><strong>Total Expenses:</strong></span>
            <span><strong>${{ number_format($total_amount, 2) }}</strong></span>
        </div>
        <div class="summary-row">
            <span>Number of Expenses:</span>
            <span>{{ $count }}</span>
        </div>
        <div class="summary-row">
            <span>Average per Expense:</span>
            <span>${{ $count > 0 ? number_format($total_amount / $count, 2) : '0.00' }}</span>
        </div>
    </div>

    @if($expenses->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Payment Method</th>
                <th style="text-align: right;">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->transaction_date->format('M j, Y') }}</td>
                <td>{{ $expense->title }}</td>
                <td>{{ $expense->category->name ?? 'N/A' }}</td>
                <td>{{ $expense->paymentMethod->name ?? 'N/A' }}</td>
                <td class="amount">${{ number_format($expense->amount, 2) }}</td>
                <td>{{ ucfirst($expense->status) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                <td class="amount">${{ number_format($total_amount, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="no-data">
        No expenses found for the selected period and filters.
    </div>
    @endif
</body>
</html>

