<div class="space-y-6">
    {{-- Basic Information --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b pb-2">Basic Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student ID</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $student['id'] ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $student['username'] ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Display Name</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $student['display_name'] ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $student['email'] ?? 'N/A' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registration Date</p>
                <p class="text-sm text-gray-900 dark:text-white mt-1">
                    {{ isset($student['registration_date']) ? \Carbon\Carbon::parse($student['registration_date'])->format('M d, Y g:i A') : 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Enrolled Courses --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b pb-2">
            Enrolled Courses
            @if(isset($student['enrolled_courses']) && count($student['enrolled_courses']) > 0)
                <span class="text-xs font-normal text-gray-500 ml-2">({{ count($student['enrolled_courses']) }})</span>
            @endif
        </h3>
        @if(isset($student['enrolled_courses']) && count($student['enrolled_courses']) > 0)
            <div class="space-y-2">
                @foreach($student['enrolled_courses'] as $course)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $course['title'] ?? 'Unknown Course' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Course ID: {{ $course['id'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @if(isset($course['progress_percent']))
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span class="font-semibold">{{ $course['progress_percent'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ $course['progress_percent'] }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-lg text-center border border-dashed border-gray-300 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No enrolled courses</p>
            </div>
        @endif
    </div>

    {{-- Order History --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b pb-2">
            Order History
            @if(isset($student['order_history']) && count($student['order_history']) > 0)
                <span class="text-xs font-normal text-gray-500 ml-2">({{ count($student['order_history']) }})</span>
            @endif
        </h3>
        @if(isset($student['order_history']) && count($student['order_history']) > 0)
            <div class="space-y-2">
                @foreach($student['order_history'] as $order)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Order #{{ $order['order_id'] ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ isset($order['date']) ? \Carbon\Carbon::parse($order['date'])->format('M d, Y') : 'N/A' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">${{ $order['total'] ?? '0.00' }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full mt-1
                                {{ ($order['status'] ?? '') === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                   (($order['status'] ?? '') === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ ucfirst($order['status'] ?? 'pending') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-lg text-center border border-dashed border-gray-300 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No order history</p>
            </div>
        @endif
    </div>
</div>

