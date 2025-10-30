<x-filament-panels::page>
    {{-- Header Actions rendered by Filament --}}
    <x-filament-actions::actions 
        :actions="$this->getCachedHeaderActions()" 
        :alignment="\Filament\Support\Enums\Alignment::End"
        class="mb-6"
    />

    <style>
        .transaction-hero {
            background: linear-gradient(135deg, {{ $transaction->type === 'income' ? '#10b981 0%, #059669' : '#ef4444 0%, #dc2626' }} 100%);
            padding: 2rem;
            border-radius: 1.25rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
        }

        .transaction-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, 0.3);
            flex-shrink: 0;
        }

        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .info-badge:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .content-card {
            background: white;
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-gradient);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .customer-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
        }

        .customer-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .customer-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .customer-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 1.5rem;
            background: linear-gradient(135deg, {{ $transaction->type === 'income' ? '#10b981 0%, #059669' : '#ef4444 0%, #dc2626' }} 100%);
            border-radius: 2px;
        }

        .dark .content-card {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .stat-card {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .customer-info-grid {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            border-color: #6b7280;
        }

        .dark .customer-label {
            color: #9ca3af;
        }

        .dark .customer-value {
            color: #f9fafb;
        }

        .dark .section-title {
            color: #f9fafb;
        }
    </style>

    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
    @elseif($error)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">Failed to Load Transaction</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $error }}</p>
        </div>
    @else
        {{-- Hero Section --}}
        <div class="transaction-hero">
            <div class="flex items-center gap-4">
                <div class="transaction-icon-wrapper">
                    @if($transaction->type === 'income')
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    @else
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-xl font-semibold">{{ $transaction->reference_number }}</h1>
                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-white/20">{{ strtoupper($transaction->type) }}</span>
                    </div>
                    <p class="text-sm text-white/90 mb-2">{{ $transaction->title }}</p>
                    <div class="flex gap-2 text-sm">
                        <div class="info-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-2xl font-bold">{{ $transaction->formatted_amount }}</span>
                        </div>
                        <div class="info-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $transaction->transaction_date->format('F j, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transaction Overview Card --}}
        <div class="content-card">
            {{-- Stats Cards --}}
            <div class="stats-grid">
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, {{ $transaction->category->color ?? '#3b82f6' }} 0%, {{ $transaction->category->color ?? '#2563eb' }} 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Category</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $transaction->category->name }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Payment Method</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $transaction->paymentMethod->name }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ ucfirst($transaction->status) }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            @if($transaction->status === 'completed')
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($transaction->status === 'pending')
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transaction Details --}}
            @if($transaction->description)
            <div class="mb-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="customer-label mb-2">Description</div>
                <div class="text-gray-900 dark:text-white">{{ $transaction->description }}</div>
            </div>
            @endif
        </div>

        {{-- Additional Info Card --}}
        <div class="content-card">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="customer-field">
                    <div class="customer-label">Currency</div>
                    <div class="customer-value">{{ $transaction->currency }}</div>
                </div>
                @if($transaction->external_reference)
                <div class="customer-field">
                    <div class="customer-label">External Reference</div>
                    <div class="customer-value">{{ $transaction->external_reference }}</div>
                </div>
                @endif
                @if($transaction->processed_at)
                <div class="customer-field">
                    <div class="customer-label">Processed At</div>
                    <div class="customer-value">{{ $transaction->processed_at->format('M d, Y g:i A') }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Linked Records Section --}}
        @if($transaction->transactionable)
        <div class="content-card">
            <h2 class="section-title">Linked Records</h2>
            
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($transaction->transactionable instanceof \App\Models\Ticket)
                            <div class="p-2 bg-blue-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ticket #{{ $transaction->transactionable->id }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $transaction->transactionable->subject }}</p>
                            </div>
                        @elseif($transaction->transactionable instanceof \App\Models\Customer)
                            <div class="p-2 bg-blue-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $transaction->transactionable->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Customer</p>
                            </div>
                        @endif
                    </div>
                    @if($transaction->transactionable instanceof \App\Models\Ticket)
                        <a href="/admin/tickets/{{ $transaction->transactionable->id }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            View Ticket
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Audit Information --}}
        <div class="content-card">
            <h2 class="section-title">Audit Trail</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="customer-field">
                    <div class="customer-label">Created By</div>
                    <div class="customer-value">{{ $transaction->createdBy?->name ?? 'System' }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->created_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
                
                @if($transaction->approved_by)
                <div class="customer-field">
                    <div class="customer-label">Approved By</div>
                    <div class="customer-value">{{ $transaction->approvedBy->name }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->approved_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
                @endif
                
                @if($transaction->processed_at)
                <div class="customer-field">
                    <div class="customer-label">Processed</div>
                    <div class="customer-value">{{ $transaction->processed_at->format('F j, Y') }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->processed_at->diffForHumans() }}</p>
                </div>
                @endif
                
                <div class="customer-field">
                    <div class="customer-label">Last Updated</div>
                    <div class="customer-value">{{ $transaction->updated_at->format('F j, Y') }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->updated_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>






