<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        try {
            $envPath = base_path('.env');

            if (! File::exists($envPath)) {
                $this->form->fill([
                    'caseer_api_url' => 'https://caseer.academy/wp-json/my-app/v1',
                    'caseer_api_secret' => '',
                    'departments' => [],
                    'positions' => [],
                ]);

                return;
            }

            $envContent = File::get($envPath);

            // Extract current values from .env
            preg_match('/CASEER_API_URL=(.*)/', $envContent, $urlMatch);
            preg_match('/CASEER_API_SECRET=(.*)/', $envContent, $secretMatch);
            preg_match('/DEPARTMENTS=(.*)/', $envContent, $departmentsMatch);
            preg_match('/POSITIONS=(.*)/', $envContent, $positionsMatch);

            // Parse departments and positions (comma-separated) and convert to repeater format
            $departments = [];
            if (! empty($departmentsMatch[1])) {
                $deptArray = array_map('trim', explode(',', $departmentsMatch[1]));
                $deptArray = array_filter($deptArray);
                $departments = array_map(fn ($name) => ['name' => $name], $deptArray);
            }

            $positions = [];
            if (! empty($positionsMatch[1])) {
                $posArray = array_map('trim', explode(',', $positionsMatch[1]));
                $posArray = array_filter($posArray);
                $positions = array_map(fn ($name) => ['name' => $name], $posArray);
            }

            $this->form->fill([
                'caseer_api_url' => $urlMatch[1] ?? 'https://caseer.academy/wp-json/my-app/v1',
                'caseer_api_secret' => $secretMatch[1] ?? '',
                'departments' => $departments,
                'positions' => $positions,
            ]);
        } catch (\Exception $e) {
            // If we can't read .env, use defaults
            $this->form->fill([
                'caseer_api_url' => 'https://caseer.academy/wp-json/my-app/v1',
                'caseer_api_secret' => '',
                'departments' => [],
                'positions' => [],
            ]);
        }
    }

    public function form(Form $form): Form
    {
        $webhookUrl = url('/api/order-webhook');

        return $form
            ->schema([
                Section::make('Caseer Academy API Configuration')
                    ->description('Configure the API connection to the main Caseer Academy website.')
                    ->schema([
                        TextInput::make('caseer_api_url')
                            ->label('API Base URL')
                            ->url()
                            ->required()
                            ->default('https://caseer.academy/wp-json/my-app/v1')
                            ->helperText('The base URL for the Caseer Academy WordPress API'),

                        TextInput::make('caseer_api_secret')
                            ->label('API Secret Key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('The X-Secret-Key header value for API authentication'),
                    ]),

                Section::make('Employee Settings')
                    ->description('Manage departments and positions available for employees.')
                    ->schema([
                        Repeater::make('departments')
                            ->label('Departments')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Department Name')
                                    ->required()
                                    ->placeholder('Engineering, Marketing, Sales, etc.')
                                    ->maxLength(255),
                            ])
                            ->defaultItems(1)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->helperText('Add all departments available in your organization. These will appear as suggestions when creating employees.'),

                        Repeater::make('positions')
                            ->label('Positions')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Position Name')
                                    ->required()
                                    ->placeholder('Software Engineer, Manager, etc.')
                                    ->maxLength(255),
                            ])
                            ->defaultItems(1)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->helperText('Add all job positions available in your organization. These will appear as suggestions when creating employees.'),
                    ]),

                Section::make('WooCommerce Webhook Setup')
                    ->description('Configure your WooCommerce store to send order notifications to this system.')
                    ->schema([
                        Placeholder::make('webhook_instructions')
                            ->label('Setup Instructions')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Add this webhook URL to your WooCommerce settings to automatically create tickets when orders are placed or updated.
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-md text-sm font-mono">
                                            '.$webhookUrl.'
                                        </code>
                                        <button 
                                            type="button"
                                            onclick="navigator.clipboard.writeText(\''.$webhookUrl.'\'); 
                                                    window.dispatchEvent(new CustomEvent(\'notify\', { detail: { message: \'Webhook URL copied to clipboard!\' } }));"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                                            title="Copy to clipboard"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
                                            📋 Webhook Configuration:
                                        </p>
                                        <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-disc list-inside">
                                            <li><strong>Topics:</strong> Order created, Order updated</li>
                                            <li><strong>Delivery URL:</strong> Use the URL above</li>
                                            <li><strong>Status:</strong> Active</li>
                                        </ul>
                                    </div>
                                </div>
                            ')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            $envPath = base_path('.env');

            // Check if file exists and is readable
            if (! File::exists($envPath)) {
                throw new \Exception('The .env file does not exist at: '.$envPath);
            }

            if (! File::isReadable($envPath)) {
                throw new \Exception('The .env file is not readable. Please check file permissions.');
            }

            $envContent = File::get($envPath);

            // Update or add CASEER_API_URL
            if (preg_match('/CASEER_API_URL=/', $envContent)) {
                $envContent = preg_replace(
                    '/CASEER_API_URL=.*/',
                    'CASEER_API_URL='.$data['caseer_api_url'],
                    $envContent
                );
            } else {
                $envContent .= "\nCASEER_API_URL=".$data['caseer_api_url'];
            }

            // Update or add CASEER_API_SECRET
            if (preg_match('/CASEER_API_SECRET=/', $envContent)) {
                $envContent = preg_replace(
                    '/CASEER_API_SECRET=.*/',
                    'CASEER_API_SECRET='.$data['caseer_api_secret'],
                    $envContent
                );
            } else {
                $envContent .= "\nCASEER_API_SECRET=".$data['caseer_api_secret'];
            }

            // Handle departments (from repeater array)
            $departments = [];
            if (! empty($data['departments']) && is_array($data['departments'])) {
                $departments = array_map(fn ($item) => $item['name'] ?? '', $data['departments']);
                $departments = array_filter($departments);
            }
            $departmentsString = implode(',', $departments);

            if (preg_match('/DEPARTMENTS=/', $envContent)) {
                $envContent = preg_replace(
                    '/DEPARTMENTS=.*/',
                    'DEPARTMENTS='.$departmentsString,
                    $envContent
                );
            } else {
                $envContent .= "\nDEPARTMENTS=".$departmentsString;
            }

            // Handle positions (from repeater array)
            $positions = [];
            if (! empty($data['positions']) && is_array($data['positions'])) {
                $positions = array_map(fn ($item) => $item['name'] ?? '', $data['positions']);
                $positions = array_filter($positions);
            }
            $positionsString = implode(',', $positions);

            if (preg_match('/POSITIONS=/', $envContent)) {
                $envContent = preg_replace(
                    '/POSITIONS=.*/',
                    'POSITIONS='.$positionsString,
                    $envContent
                );
            } else {
                $envContent .= "\nPOSITIONS=".$positionsString;
            }

            // Check if file is writable before attempting to write
            if (! File::isWritable($envPath)) {
                throw new \Exception('The .env file is not writable. Please fix permissions on your server by running: sudo chmod 664 '.$envPath.' && sudo chown www-data:www-data '.$envPath);
            }

            File::put($envPath, $envContent);

            // Clear config cache
            \Artisan::call('config:clear');

            Notification::make()
                ->title('Settings saved successfully')
                ->body('All settings including departments and positions have been updated.')
                ->success()
                ->duration(8000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to save settings')
                ->body('Error: '.$e->getMessage().' Please contact your server administrator or fix file permissions.')
                ->danger()
                ->duration(15000)
                ->send();
        }
    }

    public function testConnection(): void
    {
        try {
            // Temporarily set config values from form
            $data = $this->form->getState();
            config(['services.caseer_academy.api_url' => $data['caseer_api_url']]);
            config(['services.caseer_academy.api_secret' => $data['caseer_api_secret']]);

            $service = new \App\Services\CaseerAcademyService;
            $result = $service->getLatestStudents();

            if ($result['success']) {
                $studentCount = count($result['students']);
                Notification::make()
                    ->title('Connection successful!')
                    ->body("Successfully connected to Caseer Academy API. Found {$studentCount} student(s).")
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                Notification::make()
                    ->title('Connection failed')
                    ->body($result['error'])
                    ->danger()
                    ->duration(15000)
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Connection error')
                ->body('Failed to connect: '.$e->getMessage())
                ->danger()
                ->duration(15000)
                ->send();
        }
    }
}
