<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Statement</title>
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
        .summary-row.total {
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 13px;
        }
        .summary-row.profit {
            background-color: #e8f5e9;
            padding: 10px;
            margin-top: 5px;
            border-radius: 3px;
        }
        .summary-row.loss {
            background-color: #ffebee;
            padding: 10px;
            margin-top: 5px;
            border-radius: 3px;
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
        tr:hover {
            background-color: #f9f9f9;
        }
        .category-section {
            margin: 20px 0;
        }
        .category-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 15px 0 10px 0;
            padding: 5px 0;
            border-bottom: 2px solid #333;
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
        .section-total {
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="report-title">Profit & Loss Statement</div>
    <div class="report-period">Period: {{ $data['period']['start'] }} - {{ $data['period']['end'] }}</div>

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Income:</span>
            <span class="amount positive">{{ $data['summary']['formatted_income'] }}</span>
        </div>
        <div class="summary-row">
            <span>Total Expenses:</span>
            <span class="amount negative">{{ $data['summary']['formatted_expenses'] }}</span>
        </div>
        <div class="summary-row total {{ $data['summary']['is_profitable'] ? 'profit' : 'loss' }}">
            <span>Net {{ $data['summary']['is_profitable'] ? 'Profit' : 'Loss' }}:</span>
            <span class="amount">{{ $data['summary']['formatted_profit'] }}</span>
        </div>
        <div class="summary-row">
            <span>Profit Margin:</span>
            <span>{{ $data['summary']['profit_margin'] }}%</span>
        </div>
    </div>

    <div class="category-section">
        <div class="category-title">Income Breakdown by Category</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['income']['categories'] as $category)
                    <tr>
                        <td>{{ $category['name'] }}</td>
                        <td>{{ $category['count'] }} transactions</td>
                        <td class="amount positive">{{ $category['formatted_total'] }}</td>
                    </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Income</td>
                    <td class="amount positive">{{ $data['income']['formatted'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="category-section">
        <div class="category-title">Expenses Breakdown by Category</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['expenses']['categories'] as $category)
                    <tr>
                        <td>{{ $category['name'] }}</td>
                        <td>{{ $category['count'] }} transactions</td>
                        <td class="amount negative">{{ $category['formatted_total'] }}</td>
                    </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Expenses</td>
                    <td class="amount negative">{{ $data['expenses']['formatted'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

