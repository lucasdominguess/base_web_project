<?php
namespace App\Services;

use App\Interfaces\SanitizerInterface;

/**
 * XSS Sanitization Service - Security-focused implementation
 * Uses Laravel's native HTML encoding and input validation
 *
 * SECURITY NOTES:
 * - This service sanitizes string inputs using htmlspecialchars (HTML encode)
 * - For API endpoints receiving JSON, this provides an additional safety layer
 * - Primary defense should be output encoding in templates and JSON responses
 * - Recommended: Use Laravel's automatic JSON encoding in responses
 *
 * @package App\Services
 */
class XssCleanService implements SanitizerInterface
{
    /**
     * Clean a single string value using HTML encoding
     *
     * SECURITY: htmlspecialchars with ENT_QUOTES is the standard defense
     *
     * @param string|null $value
     * @return string|null
     */
    public function clean(string|null $value): string|null
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // HTML encode special characters - prevents XSS in most contexts
        // ENT_QUOTES encodes both double and single quotes
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Recursively clean an array of values
     * Preserves array structure while sanitizing all string values
     *
     * @param array $data
     * @return array
     */
    public function cleanArray(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = $this->clean($value);
            }
        });

        return $data;
    }
}
