<x-filament-panels::page>
    {{-- Header Actions rendered by Filament --}}
    <x-filament-actions::actions 
        :actions="$this->getCachedHeaderActions()" 
        :alignment="\Filament\Support\Enums\Alignment::End"
        class="mb-6"
    />

    <style>
        .ticket-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 1.25rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3), 0 4px 6px -2px rgba(102, 126, 234, 0.1);
        }

        .ticket-icon-wrapper {
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

        .notes-section {
            margin-bottom: 2rem;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .note-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .note-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .note-item.internal {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        .note-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .note-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .internal-badge {
            background: #f59e0b;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .attachment-item:hover {
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .attachment-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .attachment-info {
            flex: 1;
        }

        .attachment-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .attachment-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border: 2px dashed #d1d5db;
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            color: #9ca3af;
        }

        .form-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
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

        .dark .note-item {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .attachment-item {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .attachment-name {
            color: #f9fafb;
        }

        .dark .form-section {
            background: #1f2937;
            border-color: #374151;
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
            <h3 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">Failed to Load Ticket</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $error }}</p>
        </div>
    @else
        {{-- Hero Section --}}
        <div class="ticket-hero">
            <div class="flex items-center gap-4">
                <div class="ticket-icon-wrapper">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold mb-1">Ticket #{{ $ticket->id }}</h1>
                    <p class="text-sm text-purple-100 mb-2">{{ $ticket->subject }}</p>
                    <div class="flex gap-2 text-sm">
                        <div class="info-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ $ticket->customer->name }}</span>
                        </div>
                        <div class="info-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $ticket->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ticket Overview Card --}}
        <div class="content-card">
            {{-- Stats Cards --}}
            <div class="stats-grid">
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ ucfirst($ticket->status) }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Priority</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ ucfirst($ticket->priority) }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Notes</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $ticket->notes->count() }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="customer-info-grid">
                <div class="customer-field">
                    <div class="customer-label">Customer</div>
                    <div class="customer-value">{{ $ticket->customer->name }}</div>
                </div>
                <div class="customer-field">
                    <div class="customer-label">Phone</div>
                    <div class="customer-value">{{ $ticket->customer->phone_number }}</div>
                </div>
                <div class="customer-field">
                    <div class="customer-label">Email</div>
                    <div class="customer-value">{{ $ticket->customer->email ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Additional Ticket Info --}}
        <div class="content-card">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="customer-field">
                    <div class="customer-label">Course</div>
                    <div class="customer-value">{{ $ticket->course_name ?? 'N/A' }}</div>
                </div>
                <div class="customer-field">
                    <div class="customer-label">Assigned To</div>
                    <div class="customer-value">{{ $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned' }}</div>
                </div>
                <div class="customer-field">
                    <div class="customer-label">Created</div>
                    <div class="customer-value">{{ $ticket->created_at->format('M d, Y g:i A') }}</div>
                </div>
                <div class="customer-field">
                    <div class="customer-label">Last Updated</div>
                    <div class="customer-value">{{ $ticket->updated_at->format('M d, Y g:i A') }}</div>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="customer-label mb-2">Description</div>
                <div class="text-gray-900 dark:text-white">{{ $ticket->description }}</div>
            </div>
        </div>

        {{-- Payment & Finance Section --}}
        @if($ticket->total_amount)
        <div class="content-card">
            <h2 class="section-title">Payment & Financial Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Amount</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($ticket->total_amount, 2) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $ticket->currency }}</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Paid Amount</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($ticket->paid_amount ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ round($ticket->payment_progress, 1) }}% complete</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Remaining</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($ticket->remaining_amount, 2) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Outstanding balance</p>
                        </div>
                        <div class="stat-icon-wrapper">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card" style="--card-gradient: linear-gradient(135deg, {{ $ticket->payment_status === 'paid' ? '#10b981' : ($ticket->is_overdue ? '#ef4444' : '#6366f1') }} 0%, {{ $ticket->payment_status === 'paid' ? '#059669' : ($ticket->is_overdue ? '#dc2626' : '#4f46e5') }} 100%);">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Payment Status</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ ucfirst($ticket->payment_status) }}</p>
                            @if($ticket->payment_due_date)
                                <p class="text-xs text-gray-500 mt-1">Due: {{ $ticket->payment_due_date->format('M j, Y') }}</p>
                            @endif
                        </div>
                        <div class="stat-icon-wrapper">
                            @if($ticket->payment_status === 'paid')
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($ticket->is_overdue)
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            @if($ticket->order_reference || $ticket->payment_reference)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($ticket->order_reference)
                    <div class="customer-field">
                        <div class="customer-label">Order Reference</div>
                        <div class="customer-value">{{ $ticket->order_reference }}</div>
                    </div>
                    @endif
                    
                    @if($ticket->payment_reference)
                    <div class="customer-field">
                        <div class="customer-label">Payment Reference</div>
                        <div class="customer-value">{{ $ticket->payment_reference }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            
            {{-- Related Transactions --}}
            @if($ticket->transactions->count() > 0)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Related Transactions</h3>
                <div class="space-y-2">
                    @foreach($ticket->transactions as $transaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->title }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($transaction->amount, 2) }}
                                </span>
                                <a href="/admin/transactions/{{ $transaction->id }}" 
                                   class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                                    View →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Notes --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Notes & Activity --}}
                <div class="notes-section">
                    <h2 class="section-title">Notes & Activity</h2>
                    
                    {{-- Add Note Form - Using Filament Form --}}
                <div class="content-card mb-6">
                    <form wire:submit.prevent="addNote">
                        <div class="space-y-4">
                            <div>
                                <label for="noteInput" class="block text-sm font-medium text-gray-700 mb-2">
                                    Note <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    wire:model="noteInput"
                                    id="noteInput"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Add a note..."
                                    required
                                ></textarea>
                            </div>
                            
                            <div class="mt-6">
                                <button 
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring focus:ring-primary-300 disabled:opacity-25 transition"
                                >
                                    Add Note
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                    {{-- Notes List --}}
                    <div class="space-y-4">
                        @forelse($ticket->notes as $note)
                            <div class="note-item {{ $note->is_internal ? 'internal' : '' }}">
                                <div class="note-header">
                                    <div class="note-meta">
                                        <span class="font-medium">{{ $note->user->name }}</span>
                                        <span>•</span>
                                        <span>{{ $note->created_at->format('M d, Y g:i A') }}</span>
                                        @if($note->is_internal)
                                            <span class="internal-badge">Internal</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-gray-700 dark:text-gray-300">
                                    {{ $note->note }}
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No notes yet</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add the first note to start tracking this ticket's progress.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right Column: Attachments --}}
            <div class="space-y-6">
                <div class="content-card">
                    <h2 class="section-title">Attachments</h2>
                    
                    {{-- Upload Form --}}
                    <div class="form-section">
                        <div>
                            <input 
                                type="file" 
                                wire:model="newAttachment"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900 dark:file:text-primary-300"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maximum file size: 10MB</p>
                        </div>
                    </div>

                    {{-- Attachments List --}}
                    <div class="space-y-3">
                        @forelse($ticket->attachments as $attachment)
                            <div class="attachment-item">
                                <div class="attachment-icon">
                                    @if(str_contains($attachment->mime_type, 'image'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    @elseif(str_contains($attachment->mime_type, 'pdf'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-name">{{ $attachment->original_filename }}</div>
                                    <div class="attachment-meta">
                                        {{ $attachment->formatted_file_size }} • {{ $attachment->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a 
                                        href="{{ $attachment->download_url }}" 
                                        target="_blank"
                                        class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    <button 
                                        wire:click="deleteAttachment({{ $attachment->id }})"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        onclick="return confirm('Are you sure you want to delete this attachment?')"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No attachments</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload files to help track this ticket's progress.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>