<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Trend Analysis</title>
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
        .amount.positive {
            color: #2e7d32;
        }
        .amount.negative {
            color: #c62828;
        }
        .highlight-row {
            background-color: #fff3e0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="report-title">Financial Trend Analysis ({{ ucfirst($data['period']) }})</div>
    <div class="report-period">Period: {{ $data['date_range']['start'] }} - {{ $data['date_range']['end'] }}</div>

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Income:</span>
            <span class="amount positive">${{ number_format($data['summary']['total_income'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Expenses:</span>
            <span class="amount negative">${{ number_format($data['summary']['total_expenses'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Average Profit per Period:</span>
            <span class="amount">${{ number_format($data['summary']['average_profit'], 2) }}</span>
        </div>
    </div>

    @if(isset($data['summary']['best_period']))
        <div class="summary-box">
            <div class="summary-row">
                <span>Best Performing Period:</span>
                <span>{{ $data['summary']['best_period']['label'] }} - {{ $data['summary']['best_period']['formatted_profit'] }}</span>
            </div>
            <div class="summary-row">
                <span>Worst Performing Period:</span>
                <span>{{ $data['summary']['worst_period']['label'] }} - {{ $data['summary']['worst_period']['formatted_profit'] }}</span>
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th class="amount">Income</th>
                <th class="amount">Expenses</th>
                <th class="amount">Profit/Loss</th>
                <th class="amount">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['data'] as $period)
                <tr class="{{ $period['profit'] == $data['summary']['best_period']['profit'] ? 'highlight-row' : '' }}">
                    <td>{{ $period['label'] }}</td>
                    <td class="amount positive">{{ $period['formatted_income'] }}</td>
                    <td class="amount negative">{{ $period['formatted_expenses'] }}</td>
                    <td class="amount {{ $period['profit'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $period['formatted_profit'] }}
                    </td>
                    <td class="amount">{{ $period['profit_margin'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

