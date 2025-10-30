<x-filament-panels::page>
    {{-- Custom CSS embedded directly in the component --}}
    @push('styles')
    <style>
        /* Student Dashboard Custom Styles */
        .student-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem;
            border-radius: 1.5rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(102, 126, 234, 0.3), 0 10px 10px -5px rgba(102, 126, 234, 0.1);
        }

        .student-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            border: 5px solid rgba(255, 255, 255, 0.3);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 0.95rem;
        }

        .stat-card {
            background: white;
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .stat-card {
            background: rgb(31, 41, 55);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--card-gradient);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .stat-icon-wrapper {
            width: 72px;
            height: 72px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-gradient);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .content-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            padding: 2.5rem;
        }

        .dark .content-card {
            background: rgb(31, 41, 55);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .section-header {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
            color: rgb(17, 24, 39);
        }

        .dark .section-header {
            color: rgb(243, 244, 246);
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .course-item {
            background: linear-gradient(135deg, #f6f8fb 0%, #f0f3f8 100%);
            border-radius: 1.25rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border-left: 5px solid transparent;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .dark .course-item {
            background: linear-gradient(135deg, rgb(55, 65, 81) 0%, rgb(31, 41, 55) 100%);
        }

        .course-item:hover {
            transform: translateX(8px);
            border-left-color: #667eea;
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.2);
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-circle-bg {
            fill: none;
            stroke: #e5e7eb;
            stroke-width: 6;
        }

        .dark .progress-circle-bg {
            stroke: #374151;
        }

        .progress-circle {
            fill: none;
            stroke: #667eea;
            stroke-width: 6;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 0.75rem;
            border-radius: 9999px;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .order-item {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dark .order-item {
            background: linear-gradient(135deg, rgb(55, 65, 81) 0%, rgb(31, 41, 55) 100%);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .order-item:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-completed {
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            color: #065f46;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #92400e;
        }

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            border-radius: 1.25rem;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border: 2px dashed #d1d5db;
        }

        .dark .empty-state {
            background: linear-gradient(135deg, rgb(31, 41, 55) 0%, rgb(17, 24, 39) 100%);
            border-color: #374151;
        }

        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 1rem;
        }

        .dark .loading-shimmer {
            background: linear-gradient(90deg, #2a2a2a 25%, #1a1a1a 50%, #2a2a2a 75%);
            background-size: 200% 100%;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .student-hero {
                padding: 2rem;
            }
            .student-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            .stat-card {
                padding: 1.5rem;
            }
            .content-card {
                padding: 1.5rem;
            }
        }
    </style>
    @endpush

    @if ($loading)
        {{-- Loading State --}}
        <div class="space-y-6">
            <div class="loading-shimmer h-48"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="loading-shimmer h-32"></div>
                <div class="loading-shimmer h-32"></div>
                <div class="loading-shimmer h-32"></div>
            </div>
            <div class="loading-shimmer h-96"></div>
        </div>
    @elseif ($error)
        {{-- Error State --}}
        <div class="empty-state">
            <svg class="mx-auto h-16 w-16 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <h3 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">Failed to Load Student</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $error }}</p>
        </div>
    @else
        {{-- Hero Section --}}
        <div class="student-hero">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                <div class="student-avatar">
                    {{ strtoupper(substr($student['display_name'] ?? 'S', 0, 1)) }}
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-4xl md:text-5xl font-bold mb-3">{{ $student['display_name'] ?? 'N/A' }}</h1>
                    <p class="text-2xl text-purple-100 mb-4">{{ '@' . ($student['username'] ?? 'N/A') }}</p>
                    <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                        <div class="info-badge">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $student['email'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-badge">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Joined {{ isset($student['registration_date']) ? \Carbon\Carbon::parse($student['registration_date'])->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Enrolled Courses --}}
            <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Enrolled Courses</p>
                        <p class="text-5xl font-bold text-gray-900 dark:text-white">{{ count($student['enrolled_courses'] ?? []) }}</p>
                    </div>
                    <div class="stat-icon-wrapper">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Orders --}}
            <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Total Orders</p>
                        <p class="text-5xl font-bold text-gray-900 dark:text-white">{{ count($student['order_history'] ?? []) }}</p>
                    </div>
                    <div class="stat-icon-wrapper">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Student ID --}}
            <div class="stat-card" style="--card-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Student ID</p>
                        <p class="text-5xl font-bold text-gray-900 dark:text-white">#{{ $student['id'] ?? 'N/A' }}</p>
                    </div>
                    <div class="stat-icon-wrapper">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Enrolled Courses Section --}}
            <div class="lg:col-span-2">
                <div class="content-card">
                    <h2 class="section-header">Enrolled Courses</h2>

                    @if(isset($student['enrolled_courses']) && count($student['enrolled_courses']) > 0)
                        @foreach($student['enrolled_courses'] as $course)
                            <div class="course-item">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            {{ $course['title'] ?? 'Unknown Course' }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                            Course ID: {{ $course['id'] ?? 'N/A' }}
                                        </p>
                                    </div>
                                    @if(isset($course['progress_percent']))
                                        <div class="flex flex-col items-center ml-4">
                                            <svg class="progress-ring" width="70" height="70">
                                                <circle class="progress-circle-bg" cx="35" cy="35" r="30"/>
                                                <circle
                                                    class="progress-circle"
                                                    cx="35"
                                                    cy="35"
                                                    r="30"
                                                    stroke-dasharray="{{ 2 * 3.14159 * 30 }}"
                                                    stroke-dashoffset="{{ 2 * 3.14159 * 30 * (1 - $course['progress_percent'] / 100) }}"
                                                />
                                            </svg>
                                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-2">{{ $course['progress_percent'] }}%</p>
                                        </div>
                                    @endif
                                </div>
                                @if(isset($course['progress_percent']))
                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            <span class="font-semibold">Progress</span>
                                            <span class="font-bold">{{ $course['progress_percent'] }}% Complete</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                            <div class="progress-bar-fill" style="width: {{ $course['progress_percent'] }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <p class="mt-4 text-base font-semibold text-gray-500 dark:text-gray-400">No enrolled courses yet</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Courses will appear here once the student enrolls</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Order History Section --}}
            <div class="lg:col-span-1">
                <div class="content-card">
                    <h2 class="section-header">Order History</h2>

                    @if(isset($student['order_history']) && count($student['order_history']) > 0)
                        @foreach($student['order_history'] as $order)
                            <div class="order-item">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p class="text-base font-bold text-gray-900 dark:text-white">Order #{{ $order['order_id'] ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ isset($order['date']) ? \Carbon\Carbon::parse($order['date'])->format('M d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <span class="status-badge {{ ($order['status'] ?? '') === 'completed' ? 'status-completed' : 'status-pending' }}">
                                        {{ ucfirst($order['status'] ?? 'pending') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t-2 border-gray-200 dark:border-gray-700">
                                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total</span>
                                    <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($order['total'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state py-12">
                            <svg class="mx-auto h-14 w-14 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-gray-500 dark:text-gray-400">No orders yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
