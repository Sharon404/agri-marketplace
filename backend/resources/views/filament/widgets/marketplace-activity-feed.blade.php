<x-filament-widgets::widget>
    <x-filament::section heading="Marketplace Activity">
        <div class="space-y-3">
            @forelse ($activities as $activity)
                <div class="flex items-start justify-between rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $activity['type'] }} · {{ $activity['title'] }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity['description'] }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity['timestamp']?->diffForHumans() }}
                        </div>
                        <div class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ ucfirst((string) $activity['status']) }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
