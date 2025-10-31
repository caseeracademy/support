<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="flex justify-start mt-6">
            <x-filament::button type="submit">
                Create Student
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>













