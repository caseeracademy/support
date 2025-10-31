<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Report Configuration Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Report Configuration
            </x-slot>
            <x-slot name="description">
                Set up your report parameters and generate comprehensive financial reports.
            </x-slot>
            
            {{ $this->form }}
        </x-filament::section>

        {{-- Quick Statistics Cards --}}
        @if(!empty($profitLossData))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-filament::section class="bg-green-50 border-green-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $profitLossData['summary']['formatted_income'] }}</div>
                    <div class="text-sm text-green-700">Total Income</div>
                </div>
            </x-filament::section>
            
            <x-filament::section class="bg-red-50 border-red-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $profitLossData['summary']['formatted_expenses'] }}</div>
                    <div class="text-sm text-red-700">Total Expenses</div>
                </div>
            </x-filament::section>
            
            <x-filament::section class="bg-blue-50 border-blue-200">
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $profitLossData['summary']['is_profitable'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $profitLossData['summary']['formatted_profit'] }}
                    </div>
                    <div class="text-sm text-blue-700">Net Profit</div>
                </div>
            </x-filament::section>
            
            <x-filament::section class="bg-purple-50 border-purple-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $profitLossData['summary']['profit_margin'] }}%</div>
                    <div class="text-sm text-purple-700">Profit Margin</div>
                </div>
            </x-filament::section>
        </div>
        @endif

        {{-- Report Sections --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Profit & Loss Report --}}
            @if(!empty($profitLossData))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Profit & Loss Statement</span>
                        <div class="flex space-x-2">
                            <x-filament::button 
                                size="sm" 
                                color="gray"
                                wire:click="downloadProfitLossPdf"
                                icon="heroicon-o-document-arrow-down"
                            >
                                PDF
                            </x-filament::button>
                        </div>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <div class="text-sm text-gray-600">
                        Period: {{ $profitLossData['period']['start'] }} - {{ $profitLossData['period']['end'] }}
                    </div>
                    
                    {{-- Income Section --}}
                    <div>
                        <h4 class="font-semibold text-green-600 border-b border-green-200 pb-1">Income</h4>
                        <div class="mt-2 space-y-1">
                            @foreach($profitLossData['income']['categories'] as $category)
                            <div class="flex justify-between text-sm">
                                <span>{{ $category['name'] }}</span>
                                <span class="font-medium">{{ $category['formatted_total'] }}</span>
                            </div>
                            @endforeach
                            <div class="flex justify-between font-semibold text-green-600 border-t pt-1">
                                <span>Total Income</span>
                                <span>{{ $profitLossData['income']['formatted'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Expenses Section --}}
                    <div>
                        <h4 class="font-semibold text-red-600 border-b border-red-200 pb-1">Expenses</h4>
                        <div class="mt-2 space-y-1">
                            @foreach($profitLossData['expenses']['categories'] as $category)
                            <div class="flex justify-between text-sm">
                                <span>{{ $category['name'] }}</span>
                                <span class="font-medium">{{ $category['formatted_total'] }}</span>
                            </div>
                            @endforeach
                            <div class="flex justify-between font-semibold text-red-600 border-t pt-1">
                                <span>Total Expenses</span>
                                <span>{{ $profitLossData['expenses']['formatted'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Net Profit --}}
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="flex justify-between font-bold {{ $profitLossData['summary']['is_profitable'] ? 'text-green-600' : 'text-red-600' }}">
                            <span>Net Profit</span>
                            <span>{{ $profitLossData['summary']['formatted_profit'] }}</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
            @endif

            {{-- Cash Flow Report --}}
            @if(!empty($cashFlowData))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>Cash Flow Summary</span>
                        <div class="flex space-x-2">
                            <x-filament::button 
                                size="sm" 
                                color="gray"
                                wire:click="downloadCashFlowPdf"
                                icon="heroicon-o-document-arrow-down"
                            >
                                PDF
                            </x-filament::button>
                        </div>
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <div class="text-sm text-gray-600">
                        Period: {{ $cashFlowData['period']['start'] }} - {{ $cashFlowData['period']['end'] }}
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-3 rounded">
                            <div class="text-sm text-blue-700">Opening Balance</div>
                            <div class="font-semibold text-blue-600">{{ $cashFlowData['opening_balance']['formatted'] }}</div>
                        </div>
                        
                        <div class="bg-purple-50 p-3 rounded">
                            <div class="text-sm text-purple-700">Closing Balance</div>
                            <div class="font-semibold text-purple-600">{{ $cashFlowData['closing_balance']['formatted'] }}</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-green-600">Total Cash Inflow</span>
                            <span class="font-medium text-green-600">{{ $cashFlowData['summary']['formatted_inflow'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">Total Cash Outflow</span>
                            <span class="font-medium text-red-600">{{ $cashFlowData['summary']['formatted_outflow'] }}</span>
                        </div>
                        <div class="flex justify-between font-semibold border-t pt-2 {{ $cashFlowData['summary']['net_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            <span>Net Cash Flow</span>
                            <span>{{ $cashFlowData['summary']['formatted_net_change'] }}</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
            @endif
        </div>

        {{-- Transaction Summary --}}
        @if(!empty($transactionSummaryData))
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <span>Transaction Summary</span>
                    <div class="flex space-x-2">
                        <x-filament::button 
                            size="sm" 
                            color="gray"
                            wire:click="downloadTransactionSummaryPdf"
                            icon="heroicon-o-document-arrow-down"
                        >
                            PDF
                        </x-filament::button>
                    </div>
                </div>
            </x-slot>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>Period: {{ $transactionSummaryData['period']['start'] }} - {{ $transactionSummaryData['period']['end'] }}</span>
                    <span>{{ $transactionSummaryData['summary']['total_count'] }} transactions</span>
                </div>
                
                @if($transactionSummaryData['filters']['type'] || $transactionSummaryData['filters']['category_name'] || $transactionSummaryData['filters']['payment_method_name'])
                <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded">
                    <strong>Filters:</strong>
                    @if($transactionSummaryData['filters']['type'])
                        Type: {{ ucfirst($transactionSummaryData['filters']['type']) }}
                    @endif
                    @if($transactionSummaryData['filters']['category_name'])
                        | Category: {{ $transactionSummaryData['filters']['category_name'] }}
                    @endif
                    @if($transactionSummaryData['filters']['payment_method_name'])
                        | Payment Method: {{ $transactionSummaryData['filters']['payment_method_name'] }}
                    @endif
                </div>
                @endif
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-lg font-semibold text-green-600">{{ $transactionSummaryData['summary']['income_count'] }}</div>
                        <div class="text-sm text-gray-600">Income Transactions</div>
                        <div class="text-sm font-medium text-green-600">{{ $transactionSummaryData['summary']['formatted_income'] }}</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-lg font-semibold text-red-600">{{ $transactionSummaryData['summary']['expense_count'] }}</div>
                        <div class="text-sm text-gray-600">Expense Transactions</div>
                        <div class="text-sm font-medium text-red-600">{{ $transactionSummaryData['summary']['formatted_expenses'] }}</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-lg font-semibold text-blue-600">{{ $transactionSummaryData['summary']['total_count'] }}</div>
                        <div class="text-sm text-gray-600">Total Transactions</div>
                        <div class="text-sm font-medium text-blue-600">{{ $transactionSummaryData['summary']['formatted_total'] }}</div>
                    </div>
                </div>
                
                {{-- Recent Transactions Table --}}
                @if(count($transactionSummaryData['transactions']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(collect($transactionSummaryData['transactions'])->take(10) as $transaction)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $transaction['date'] }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $transaction['type'] === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($transaction['type']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $transaction['title'] }}</td>
                                <td class="px-4 py-2 text-sm font-medium {{ $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction['formatted_amount'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $transaction['category'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    @if(count($transactionSummaryData['transactions']) > 10)
                    <div class="text-center text-sm text-gray-500 mt-2">
                        Showing 10 of {{ count($transactionSummaryData['transactions']) }} transactions
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </x-filament::section>
        @endif

        {{-- Additional Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::section>
                <x-slot name="heading">Customer Payment History</x-slot>
                <x-slot name="description">Generate detailed payment history reports for customers.</x-slot>
                
                <x-filament::button 
                    wire:click="downloadCustomerPaymentsPdf"
                    icon="heroicon-o-document-arrow-down"
                    color="gray"
                >
                    Download Customer Payment Report
                </x-filament::button>
            </x-filament::section>
            
            <x-filament::section>
                <x-slot name="heading">Trend Analysis</x-slot>
                <x-slot name="description">Analyze financial trends over time with detailed charts.</x-slot>
                
                <x-filament::button 
                    wire:click="downloadTrendAnalysisPdf"
                    icon="heroicon-o-document-arrow-down"
                    color="gray"
                >
                    Download Trend Analysis Report
                </x-filament::button>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>






