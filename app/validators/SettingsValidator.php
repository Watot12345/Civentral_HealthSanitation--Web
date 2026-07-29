<?php
// app/validators/SettingsValidator.php

namespace App\Validators;

use DateTime;
use DateTimeZone;

class SettingsValidator
{
    /**
     * Validate key-value array against validation rules
     */
    public function validate(array $data, array $rulesMap): array
    {
        $errors = [];

        foreach ($rulesMap as $field => $rulesString) {
            $value = $data[$field] ?? null;
            $fieldErrors = $this->validateField($field, $value, $rulesString);
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }

        return $errors;
    }

    /**
     * Validate single setting field against rules string (e.g. "required|integer|min:1|max:100")
     */
    public function validateField(string $field, mixed $value, string $rulesString): array
    {
        $fieldErrors = [];
        $rules = explode('|', $rulesString);

        $isRequired = in_array('required', $rules, true);
        $isNullable = in_array('nullable', $rules, true);

        if (($value === null || $value === '') && !$isRequired) {
            return []; // Skip if optional and blank
        }

        if (($value === null || $value === '') && $isRequired) {
            $fieldErrors[] = "The {$field} field is required.";
            return $fieldErrors;
        }

        foreach ($rules as $rule) {
            if ($rule === 'required' || $rule === 'nullable') {
                continue;
            }

            $ruleParts = explode(':', $rule, 2);
            $ruleName = $ruleParts[0];
            $ruleParam = $ruleParts[1] ?? null;

            switch ($ruleName) {
                case 'integer':
                    if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== '0' && $value !== 0) {
                        $fieldErrors[] = "The {$field} must be a valid integer.";
                    }
                    break;

                case 'float':
                case 'numeric':
                    if (!is_numeric($value)) {
                        $fieldErrors[] = "The {$field} must be a numeric value.";
                    }
                    break;

                case 'boolean':
                    $boolValues = [true, false, 1, 0, '1', '0', 'true', 'false', 'TRUE', 'FALSE'];
                    if (!in_array($value, $boolValues, true)) {
                        $fieldErrors[] = "The {$field} must be a boolean (true/false).";
                    }
                    break;

                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fieldErrors[] = "The {$field} must be a valid email address.";
                    }
                    break;

                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $fieldErrors[] = "The {$field} must be a valid URL.";
                    }
                    break;

                case 'json':
                    if (is_string($value)) {
                        json_decode($value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $fieldErrors[] = "The {$field} must be valid JSON.";
                        }
                    } elseif (!is_array($value)) {
                        $fieldErrors[] = "The {$field} must be valid JSON object or array.";
                    }
                    break;

                case 'ip':
                    if (!filter_var($value, FILTER_VALIDATE_IP)) {
                        $fieldErrors[] = "The {$field} must be a valid IP address.";
                    }
                    break;

                case 'cidr':
                    if (!$this->validateCidr((string)$value)) {
                        $fieldErrors[] = "The {$field} must be a valid CIDR notation.";
                    }
                    break;

                case 'enum':
                    if ($ruleParam !== null) {
                        $allowed = array_map('trim', explode(',', $ruleParam));
                        if (!in_array((string)$value, $allowed, true)) {
                            $fieldErrors[] = "The {$field} must be one of: " . implode(', ', $allowed);
                        }
                    }
                    break;

                case 'timezone':
                    if (!in_array((string)$value, DateTimeZone::listIdentifiers(), true)) {
                        $fieldErrors[] = "The {$field} must be a valid timezone (e.g. Asia/Manila).";
                    }
                    break;

                case 'cron':
                    if (!$this->validateCron((string)$value)) {
                        $fieldErrors[] = "The {$field} must be a valid 5-part cron expression.";
                    }
                    break;

                case 'date':
                    if (DateTime::createFromFormat('Y-m-d', (string)$value) === false) {
                        $fieldErrors[] = "The {$field} must be a date in Y-m-d format.";
                    }
                    break;

                case 'time':
                    if (DateTime::createFromFormat('H:i:s', (string)$value) === false && DateTime::createFromFormat('H:i', (string)$value) === false) {
                        $fieldErrors[] = "The {$field} must be a time in H:i or H:i:s format.";
                    }
                    break;

                case 'min':
                    if ($ruleParam !== null && is_numeric($value) && (float)$value < (float)$ruleParam) {
                        $fieldErrors[] = "The {$field} must be at least {$ruleParam}.";
                    } elseif ($ruleParam !== null && is_string($value) && strlen($value) < (int)$ruleParam) {
                        $fieldErrors[] = "The {$field} must be at least {$ruleParam} characters.";
                    }
                    break;

                case 'max':
                    if ($ruleParam !== null && is_numeric($value) && (float)$value > (float)$ruleParam) {
                        $fieldErrors[] = "The {$field} cannot exceed {$ruleParam}.";
                    } elseif ($ruleParam !== null && is_string($value) && strlen($value) > (int)$ruleParam) {
                        $fieldErrors[] = "The {$field} cannot exceed {$ruleParam} characters.";
                    }
                    break;

                case 'regex':
                    if ($ruleParam !== null && @preg_match($ruleParam, (string)$value) !== 1) {
                        $fieldErrors[] = "The {$field} format is invalid.";
                    }
                    break;
            }
        }

        return $fieldErrors;
    }

    private function validateCidr(string $cidr): bool
    {
        $parts = explode('/', $cidr);
        if (count($parts) !== 2) {
            return false;
        }
        [$ip, $netmask] = $parts;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $netmaskInt = (int)$netmask;
        return $netmaskInt >= 0 && $netmaskInt <= 32;
    }

    private function validateCron(string $expression): bool
    {
        $parts = preg_split('/\s+/', trim($expression));
        return count($parts) === 5;
    }
}
