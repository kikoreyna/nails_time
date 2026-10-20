<?php

namespace App\Enums;

use App\Models\Setting;

enum Currency: string
{
    case Mxn = 'MXN';
    case Usd = 'USD';

    public const SETTING_KEY = 'currency';

    public static function current(): self
    {
        return self::tryFrom((string) Setting::getValue(self::SETTING_KEY)) ?? self::Mxn;
    }

    public function label(): string
    {
        return match ($this) {
            self::Mxn => 'Pesos mexicanos (MXN)',
            self::Usd => 'Dólares estadounidenses (USD)',
        };
    }

    public function locale(): string
    {
        return match ($this) {
            self::Mxn => 'es-MX',
            self::Usd => 'en-US',
        };
    }

    public function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return '$'.number_format((float) $amount, $decimals).' '.$this->value;
    }
}
