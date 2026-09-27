<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class MaskSensitiveDataProcessor implements ProcessorInterface
{
    private const array KEYS_TO_MASK = ['client_secret', 'password', 'card_number', 'cvv', 'token', 'api_key'];

    public function __invoke(LogRecord $record): LogRecord
    {
        if (!empty($record->context)) {
            $record = $record->with(context: $this->maskArray($record->context));
        }
        return $record;
    }

    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskArray($value);
            } elseif (in_array(strtolower((string)$key), self::KEYS_TO_MASK, true)) {
                $data[$key] = '********';
            }
        }
        return $data;
    }
}
