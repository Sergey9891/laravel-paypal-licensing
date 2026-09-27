'single' => [
    'driver' => 'single',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'replace_placeholders' => true,
    // Просто добавляем наш класс в массив процессоров логгера Laravel
    'processors' => [
        \App\Logging\MaskSensitiveDataProcessor::class,
    ],
],

