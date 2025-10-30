<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>
            
            <x-filament::button 
                color="info" 
                wire:click="testConnection"
                type="button">
                Test Connection
            </x-filament::button>
        </div>
    </form>
    
    <script>
        window.addEventListener('notify', (event) => {
            if (typeof Filament !== 'undefined') {
                new FilamentNotification()
                    .title(event.detail.message)
                    .success()
                    .send();
            }
        });
    </script>
</x-filament-panels::page>
