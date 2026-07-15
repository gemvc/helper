<?php
namespace Gemvc\Helper;

/**
 * HTTP request input type validation for GEMVC schemas.
 *
 * Used by gemvc/library Request::definePostSchema() and direct validation calls.
 * Type names are case-insensitive. Class/interface names are also supported as $type.
 *
 * @since 1.0.0
 * @since 1.1.0 decimal, hex, uuid, slug, positive_int, timestamp, jsonb
 */
class TypeChecker
{

    /**
     * Checks whether a value matches the given type (and optional constraints).
     *
     * **Scalar types:** string, int, integer, number, float, double, bool, boolean, null
     *
     * **Schema types (v1.1.0):**
     * - decimal — fixed-precision numeric string (default 10,2); also decimal:P,S (e.g. decimal:12,4)
     * - hex — hexadecimal string [0-9a-fA-F]
     * - uuid — RFC 4122 UUID (versions 1–5)
     * - slug — lowercase URL slug (e.g. my-product-123)
     * - positive_int — integer &gt; 0 (no leading zeros)
     * - timestamp — non-negative Unix seconds
     *
     * **Formatted strings:** email, url, date, datetime, json, jsonb (alias of json — same validation; use jsonb in $_type_map for PostgreSQL JSONB)
     *
     * **Network:** ip, ipv4, ipv6
     *
     * **Structural:** array, object, callable, resource
     *
     * **Class check:** pass a class or interface name (or object) as $type
     *
     * @param mixed $type Type name string, or class/interface object for instanceof checks
     * @param mixed $value Value to validate (typically from HTTP request input)
     * @param array<string, mixed> $options Per-type constraints:
     *   - string: minLength, maxLength, regex
     *   - float/double: min, max
     *   - decimal: precision, scale, min, max (min/max as strings; uses bccomp when ext-bcmath available)
     *   - hex: minLength, maxLength
     *   - positive_int, timestamp: min, max (integers)
     *   - date, datetime: format (default Y-m-d / Y-m-d H:i:s)
     *
     * @return bool True when the value matches the type and all options; false otherwise
     *
     * @example TypeChecker::check('decimal', '19.99')
     * @example TypeChecker::check('decimal:12,4', '12345678.1234')
     * @example TypeChecker::check('uuid', '550e8400-e29b-41d4-a716-446655440000')
     * @example TypeChecker::check('slug', 'my-product')
     * @example TypeChecker::check('positive_int', '42', ['min' => 1, 'max' => 100])
     * @example TypeChecker::check('string', 'hello', ['minLength' => 3, 'maxLength' => 50])
     */
    public static function check(mixed $type, mixed $value, array $options = []): bool
    {
        if (is_string($type)) {
            $typeLower = strtolower($type);
            if (preg_match('/^decimal(?::(\d+),(\d+))?$/i', $type, $matches)) {
                if (!isset($options['precision']) && isset($matches[1])) {
                    $options['precision'] = (int) $matches[1];
                }
                if (!isset($options['scale']) && isset($matches[2])) {
                    $options['scale'] = (int) $matches[2];
                }
                $typeLower = 'decimal';
            }

            switch ($typeLower) {
                case 'string':
                    return self::checkString($value, $options);
                case 'int':
                    return is_numeric($value);
                case 'integer':
                    return is_numeric($value);
                case 'number':
                    return is_numeric($value);
                case 'float':
                    return self::checkFloat($value, $options);
                case 'double':
                    return self::checkFloat($value, $options);
                case 'decimal':
                    return self::checkDecimal($value, $options);
                case 'hex':
                    return self::checkHex($value, $options);
                case 'uuid':
                    return self::checkUuid($value);
                case 'slug':
                    return self::checkSlug($value);
                case 'positive_int':
                    return self::checkPositiveInt($value, $options);
                case 'timestamp':
                    return self::checkTimestamp($value, $options);
                case 'bool':
                    return is_bool($value);
                case 'boolean':
                    return is_bool($value);
                case 'array':
                    return is_array($value);
                case 'object':
                    return is_object($value);
                case 'callable':
                    return is_callable($value);
                case 'resource':
                    return is_resource($value);
                case 'null':
                    return is_null($value);
                case 'email':
                    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
                case 'url':
                    return (bool) filter_var($value, FILTER_VALIDATE_URL);
                case 'date':
                    return self::checkDate($value, $options);
                case 'datetime':
                    return self::checkDateTime($value, $options);
                case 'json':
                case 'jsonb':
                    return self::checkJson($value);
                case 'ip':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP);
                case 'ipv4':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
                case 'ipv6':
                    return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
                default:
                    if (class_exists($type) || interface_exists($type)) {
                        return $value instanceof $type;
                    }
                    return false; // Unknown type
            }
        } elseif (is_object($type)) {
            return $value instanceof $type;
        } else {
            return false; // Invalid type for $type argument
        }
    }


    /**
     * Checks if a value is a string and meets the given options.
     *
     * @param mixed $value The value to check.
     * @param array<string, mixed> $options The options to check against.
     * @return bool True if the value is a string and meets the options, false otherwise.
     */
    private static function checkString(mixed $value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        if (isset($options['minLength']) && strlen($value) < $options['minLength']) {
            return false;
        }
        if (isset($options['maxLength']) && strlen($value) > $options['maxLength']) {
            return false;
        }
        if (isset($options['regex'])) {
            if (!is_string($options['regex']) || !preg_match($options['regex'], $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if a value is a float and meets the given options.
     *
     * @param mixed $value The value to check.
     * @param array<string, mixed> $options The options to check against.
     * @return bool True if the value is a float and meets the options, false otherwise.
     */
    private static function checkFloat(mixed $value, array $options): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
            return false;
        }
        if (isset($options['min']) && $value < $options['min']) {
            return false;
        }
        if (isset($options['max']) && $value > $options['max']) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a value is a date string and meets the given format.
     *
     * @param mixed $value The value to check.
     * @param array<string, mixed> $options The options to check against.
     * @return bool True if the value is a date string and meets the format, false otherwise.
     */
    private static function checkDate($value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $format = $options['format'] ?? 'Y-m-d';
        if (!is_string($format)) {
            $format = 'Y-m-d';
        }
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Checks if a value is a datetime string and meets the given format.
     *
     * @param mixed $value The value to check.
     * @param array<string, mixed> $options The options to check against.
     * @return bool True if the value is a datetime string and meets the format, false otherwise.
     */
    private static function checkDateTime(mixed $value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $format = $options['format'] ?? 'Y-m-d H:i:s';
        if (!is_string($format)) {
            $format = 'Y-m-d H:i:s';
        }
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Valid JSON string. Used by types json and jsonb (same rules; jsonb maps to PostgreSQL JSONB in gemvc/library).
     */
    private static function checkJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        try {
            json_decode($value, null, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * Validates a fixed-precision decimal (money, rates). Rejects scientific notation.
     *
     * @param array<string, mixed> $options precision (default 10), scale (default 2), optional min/max strings
     */
    private static function checkDecimal(mixed $value, array $options): bool
    {
        $precision = isset($options['precision']) && is_int($options['precision'])
            ? $options['precision']
            : 10;
        $scale = isset($options['scale']) && is_int($options['scale'])
            ? $options['scale']
            : 2;

        if ($precision < 1 || $precision > 65 || $scale < 0 || $scale > $precision) {
            return false;
        }

        $normalized = self::normalizeDecimalValue($value, $scale);
        if ($normalized === null) {
            return false;
        }

        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return false;
        }

        $unsigned = str_starts_with($normalized, '-')
            ? substr($normalized, 1)
            : $normalized;
        $parts = explode('.', $unsigned, 2);
        $integerPart = $parts[0];
        $fractionalPart = $parts[1] ?? '';

        if (strlen($fractionalPart) > $scale) {
            return false;
        }

        $maxIntegerDigits = $precision - $scale;
        if (strlen($integerPart) > $maxIntegerDigits) {
            return false;
        }

        if (isset($options['min'])) {
            $min = self::decimalBoundToString($options['min']);
            if ($min === null || !self::compareDecimal($normalized, $min, 'min')) {
                return false;
            }
        }

        if (isset($options['max'])) {
            $max = self::decimalBoundToString($options['max']);
            if ($max === null || !self::compareDecimal($normalized, $max, 'max')) {
                return false;
            }
        }

        return true;
    }

    /** Coerces int/float/string input to a trimmed decimal string for scale validation. */
    private static function normalizeDecimalValue(mixed $value, int $scale): ?string
    {
        if (is_int($value)) {
            return number_format($value, $scale, '.', '');
        }

        if (is_float($value)) {
            return number_format($value, $scale, '.', '');
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function decimalBoundToString(mixed $bound): ?string
    {
        if (is_string($bound) || is_int($bound) || is_float($bound)) {
            return (string) $bound;
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true numeric-string $value
     */
    private static function isDecimalNumericString(string $value): bool
    {
        return preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1;
    }

    /** Compares decimal strings; uses bccomp when ext-bcmath is available. */
    private static function compareDecimal(string $value, string $bound, string $mode): bool
    {
        if (
            function_exists('bccomp')
            && self::isDecimalNumericString($value)
            && self::isDecimalNumericString($bound)
        ) {
            $cmp = bccomp($value, $bound, 10);

            return $mode === 'min' ? $cmp >= 0 : $cmp <= 0;
        }

        $floatValue = (float) $value;
        $floatBound = (float) $bound;

        return $mode === 'min'
            ? $floatValue >= $floatBound
            : $floatValue <= $floatBound;
    }

    /**
     * Hexadecimal string [0-9a-fA-F]. No 0x prefix. Rejects empty strings.
     *
     * @param array<string, mixed> $options minLength, maxLength
     */
    private static function checkHex(mixed $value, array $options): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || preg_match('/^[0-9a-fA-F]+$/', $trimmed) !== 1) {
            return false;
        }

        $length = strlen($trimmed);
        if (isset($options['minLength']) && is_int($options['minLength']) && $length < $options['minLength']) {
            return false;
        }
        if (isset($options['maxLength']) && is_int($options['maxLength']) && $length > $options['maxLength']) {
            return false;
        }

        return true;
    }

    /** RFC 4122 UUID (versions 1–5). Case-insensitive input. */
    private static function checkUuid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $trimmed = strtolower(trim($value));

        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $trimmed
        ) === 1;
    }

    /** Lowercase URL slug: letters, digits, hyphens (e.g. my-product-123). */
    private static function checkSlug(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }

    /**
     * Strict positive integer (&gt; 0). Accepts int or numeric string without leading zeros.
     *
     * @param array<string, mixed> $options min, max (integer bounds)
     */
    private static function checkPositiveInt(mixed $value, array $options): bool
    {
        $normalized = self::normalizePositiveIntegerString($value);
        if ($normalized === null) {
            return false;
        }

        $intValue = (int) $normalized;

        if (isset($options['min']) && is_int($options['min']) && $intValue < $options['min']) {
            return false;
        }
        if (isset($options['max']) && is_int($options['max']) && $intValue > $options['max']) {
            return false;
        }

        return true;
    }

    /**
     * Non-negative Unix timestamp in seconds. Accepts int or numeric string.
     *
     * @param array<string, mixed> $options min, max (Unix seconds)
     */
    private static function checkTimestamp(mixed $value, array $options): bool
    {
        $normalized = self::normalizeNonNegativeIntegerString($value);
        if ($normalized === null) {
            return false;
        }

        $intValue = (int) $normalized;

        if (isset($options['min']) && is_int($options['min']) && $intValue < $options['min']) {
            return false;
        }
        if (isset($options['max']) && is_int($options['max']) && $intValue > $options['max']) {
            return false;
        }

        return true;
    }

    /** @return non-empty-string|null Digits only, first digit 1–9 (rejects 0, 01, 1.5, 1e5). */
    private static function normalizePositiveIntegerString(mixed $value): ?string
    {
        if (is_int($value)) {
            return $value > 0 ? (string) $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if (preg_match('/^[1-9]\d*$/', $trimmed) !== 1) {
            return null;
        }

        return $trimmed;
    }

    /** @return non-empty-string|null Digits only, zero allowed (rejects negatives, floats, 1e5). */
    private static function normalizeNonNegativeIntegerString(mixed $value): ?string
    {
        if (is_int($value)) {
            return $value >= 0 ? (string) $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if (preg_match('/^\d+$/', $trimmed) !== 1) {
            return null;
        }

        return $trimmed;
    }

}

