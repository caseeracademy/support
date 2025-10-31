<div class="p-8 text-center">
    <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    
    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Failed to Load Student Details</h3>
    
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        {{ $error ?? 'An unexpected error occurred while fetching student details from the API.' }}
    </p>
    
    <div class="mt-6">
        <button 
            type="button" 
            onclick="window.location.reload()" 
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Try Again
        </button>
    </div>
</div>













