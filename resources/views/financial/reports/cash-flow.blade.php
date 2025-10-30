<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Flow Statement</title>
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
        .summary-row.highlight {
            background-color: #e3f2fd;
            padding: 10px;
            margin-top: 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }
        th {
            background-color: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        .amount {
            text-align: right;
        }
        .amount.positive {
            color: #2e7d32;
        }
        .amount.negative {
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="report-title">Cash Flow Statement</div>
    <div class="report-period">Period: {{ $data['period']['start'] }} - {{ $data['period']['end'] }}</div>

    <div class="summary-box">
        <div class="summary-row">
            <span>Opening Balance:</span>
            <span class="amount">{{ $data['opening_balance']['formatted'] }}</span>
        </div>
        <div class="summary-row">
            <span>Total Cash Inflow:</span>
            <span class="amount positive">{{ $data['summary']['formatted_inflow'] }}</span>
        </div>
        <div class="summary-row">
            <span>Total Cash Outflow:</span>
            <span class="amount negative">{{ $data['summary']['formatted_outflow'] }}</span>
        </div>
        <div class="summary-row">
            <span>Net Change:</span>
            <span class="amount">{{ $data['summary']['formatted_net_change'] }}</span>
        </div>
        <div class="summary-row highlight">
            <span>Closing Balance:</span>
            <span class="amount">{{ $data['closing_balance']['formatted'] }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="amount">Income</th>
                <th class="amount">Expenses</th>
                <th class="amount">Net Flow</th>
                <th class="amount">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['daily_flows'] as $flow)
                <tr>
                    <td>{{ $flow['date'] }}</td>
                    <td class="amount positive">{{ $flow['formatted_income'] }}</td>
                    <td class="amount negative">{{ $flow['formatted_expenses'] }}</td>
                    <td class="amount">{{ $flow['formatted_net_flow'] }}</td>
                    <td class="amount">{{ $flow['formatted_balance'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

