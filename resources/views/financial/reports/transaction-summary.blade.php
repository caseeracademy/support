<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Summary</title>
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
            border-left: 3px solid #2196f3;
            font-size: 10px;
        }
        .summary-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
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
            background-color: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        .amount {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge.income {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge.expense {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="report-title">Transaction Summary</div>
    <div class="report-period">Period: {{ $data['period']['start'] }} - {{ $data['period']['end'] }}</div>

    @if($data['filters']['type'] || $data['filters']['category_name'] || $data['filters']['payment_method_name'])
        <div class="filters">
            <strong>Filters Applied:</strong>
            @if($data['filters']['type'])
                Type: {{ ucfirst($data['filters']['type']) }}
            @endif
            @if($data['filters']['category_name'])
                | Category: {{ $data['filters']['category_name'] }}
            @endif
            @if($data['filters']['payment_method_name'])
                | Payment Method: {{ $data['filters']['payment_method_name'] }}
            @endif
        </div>
    @endif

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Transactions:</span>
            <span>{{ $data['summary']['total_count'] }}</span>
        </div>
        <div class="summary-row">
            <span>Income Transactions:</span>
            <span>{{ $data['summary']['income_count'] }} ({{ $data['summary']['formatted_income'] }})</span>
        </div>
        <div class="summary-row">
            <span>Expense Transactions:</span>
            <span>{{ $data['summary']['expense_count'] }} ({{ $data['summary']['formatted_expenses'] }})</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Title</th>
                <th>Category</th>
                <th>Payment Method</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['transactions'] as $transaction)
                <tr>
                    <td>{{ $transaction['date'] }}</td>
                    <td>{{ $transaction['reference'] }}</td>
                    <td>
                        <span class="badge {{ $transaction['type'] }}">
                            {{ ucfirst($transaction['type']) }}
                        </span>
                    </td>
                    <td>{{ $transaction['title'] }}</td>
                    <td>{{ $transaction['category'] }}</td>
                    <td>{{ $transaction['payment_method'] }}</td>
                    <td class="amount">{{ $transaction['formatted_amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

