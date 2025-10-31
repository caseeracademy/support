<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Stats --}}
        @php
            $query = $this->getFilteredExpensesQuery();
            $total = $query->sum('amount');
            $count = $query->count();
            $startDate = $this->start_date ? \Carbon\Carbon::parse($this->start_date)->format('M j, Y') : 'All Time';
            $endDate = $this->end_date ? \Carbon\Carbon::parse($this->end_date)->format('M j, Y') : 'All Time';
        @endphp
        <x-filament::section class="bg-red-50 border-red-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-red-600">${{ number_format($total, 2) }}</div>
                    <div class="text-sm text-red-700">Total Expenses</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600">{{ $count }}</div>
                    <div class="text-sm text-gray-700">Number of Expenses</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600">{{ $startDate }} - {{ $endDate }}</div>
                    <div class="text-sm text-gray-700">Period</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Filter Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Filter Expenses
            </x-slot>
            <x-slot name="description">
                Set date range and filters to view expenses
            </x-slot>
            
            {{ $this->form }}
        </x-filament::section>

        {{-- Expenses Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Expenses List
            </x-slot>
            <x-slot name="description">
                View and manage all expense transactions
            </x-slot>
            
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
