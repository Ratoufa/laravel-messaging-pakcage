<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Support;

use Illuminate\Support\Facades\Config;

final class PhoneFormatter
{
    private const string DEFAULT_COUNTRY_CODE = '228';

    private const int MIN_PHONE_LENGTH = 10;

    private const int MAX_PHONE_LENGTH = 15;

    private const int LOCAL_PHONE_LENGTH = 8;

    public static function format(string $phone, ?string $defaultCountryCode = null): string
    {
        $defaultCountryCode ??= Config::string('messaging.phone.default_country_code', self::DEFAULT_COUNTRY_CODE);

        $phone = (string) preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = mb_substr($phone, 2);
        }

        if (mb_strlen($phone) === self::LOCAL_PHONE_LENGTH && ! str_starts_with($phone, $defaultCountryCode)) {
            return $defaultCountryCode.$phone;
        }

        return $phone;
    }

    /**
     * @param  list<string>  $phones
     * @return list<string>
     */
    public static function formatMany(array $phones, ?string $defaultCountryCode = null): array
    {
        return array_map(
            fn (string $phone): string => self::format($phone, $defaultCountryCode),
            $phones
        );
    }

    public static function isValid(string $phone): bool
    {
        $formatted = self::format($phone);

        return mb_strlen($formatted) >= self::MIN_PHONE_LENGTH
            && mb_strlen($formatted) <= self::MAX_PHONE_LENGTH;
    }

    public static function formatForWhatsApp(string $phone, ?string $defaultCountryCode = null): string
    {
        $formatted = self::format($phone, $defaultCountryCode);

        return 'whatsapp:+'.$formatted;
    }

    public static function normalize(string $phone, ?string $defaultCountryCode = null): string
    {
        return '+'.self::format($phone, $defaultCountryCode);
    }
}
