<?php

namespace Aeros\Src\Traits;

/**
 * Validatable Trait
 *
 * Provides data validation capabilities with common validation rules:
 * - required, optional
 * - email, url, ip
 * - numeric, integer, float, boolean
 * - min, max (for strings and numbers)
 * - regex, alpha, alphanumeric
 * - date, datetime
 * - unique (database check)
 * - in, not_in (whitelist/blacklist)
 *
 * @package Aeros\Src\Traits
 */
trait Validatable
{
    /**
     * Validation errors.
     *
     * @var array
     */
    protected array $validationErrors = [];

    /**
     * Validate data against rules.
     *
     * Usage:
     *   $this->validate($data, [
     *       'email' => 'required|email|unique:users',
     *       'password' => 'required|min:8',
     *       'age' => 'required|integer|min:18|max:100'
     *   ]);
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if valid
     * @throws \InvalidArgumentException If validation fails and exceptions enabled
     */
    protected function validate(array $data, array $rules): bool
    {
        $this->validationErrors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;

            foreach ($rulesList as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }

        if (!empty($this->validationErrors) && $this->shouldThrowExceptions()) {
            throw new \InvalidArgumentException(
                "Validation failed: " . implode(', ', array_map(
                    fn($field, $errors) => "{$field}: " . implode('; ', $errors),
                    array_keys($this->validationErrors),
                    $this->validationErrors
                ))
            );
        }

        return empty($this->validationErrors);
    }

    /**
     * Apply a single validation rule.
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule string
     * @param array $allData All data (for unique checks, etc.)
     * @return void
     */
    protected function applyRule(string $field, mixed $value, string $rule, array $allData): void
    {
        // Parse rule and parameters (e.g., "min:8" => rule="min", params=["8"])
        [$ruleName, $params] = $this->parseRule($rule);

        // Check if rule method exists
        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $result = $this->$method($value, $params, $allData);

            if ($result !== true) {
                $this->addValidationError($field, $result ?: "The {$field} field failed {$ruleName} validation.");
            }
        } else {
            $this->logError("Unknown validation rule: {$ruleName}");
        }
    }

    /**
     * Parse rule string into name and parameters.
     *
     * @param string $rule Rule string (e.g., "min:8" or "in:active,pending")
     * @return array [ruleName, parameters]
     */
    protected function parseRule(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            [$name, $paramsString] = explode(':', $rule, 2);
            $params = explode(',', $paramsString);
            return [$name, $params];
        }

        return [$rule, []];
    }

    /**
     * Add validation error.
     *
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    protected function addValidationError(string $field, string $message): void
    {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }

        $this->validationErrors[$field][] = $message;
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->validationErrors);
    }

    // ============================================
    // Validation Rules
    // ============================================

    /**
     * Required rule - value must be present and not empty.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateRequired(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
            return "This field is required.";
        }

        return true;
    }

    /**
     * Email rule - value must be valid email format.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateEmail(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true; // Allow empty unless 'required' is also specified
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Must be a valid email address.";
        }

        return true;
    }

    /**
     * URL rule - value must be valid URL.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateUrl(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return "Must be a valid URL.";
        }

        return true;
    }

    /**
     * Integer rule - value must be an integer.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateInteger(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0 && $value !== '0') {
            return "Must be an integer.";
        }

        return true;
    }

    /**
     * Numeric rule - value must be numeric.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateNumeric(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!is_numeric($value)) {
            return "Must be numeric.";
        }

        return true;
    }

    /**
     * Min rule - minimum length for strings, minimum value for numbers.
     *
     * @param mixed $value
     * @param array $params [minValue]
     * @param array $allData
     * @return bool|string
     */
    protected function validateMin(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $min = (int) $params[0];

        if (is_numeric($value)) {
            if ($value < $min) {
                return "Must be at least {$min}.";
            }
        } else {
            if (mb_strlen($value) < $min) {
                return "Must be at least {$min} characters.";
            }
        }

        return true;
    }

    /**
     * Max rule - maximum length for strings, maximum value for numbers.
     *
     * @param mixed $value
     * @param array $params [maxValue]
     * @param array $allData
     * @return bool|string
     */
    protected function validateMax(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $max = (int) $params[0];

        if (is_numeric($value)) {
            if ($value > $max) {
                return "Must be at most {$max}.";
            }
        } else {
            if (mb_strlen($value) > $max) {
                return "Must be at most {$max} characters.";
            }
        }

        return true;
    }

    /**
     * Unique rule - value must be unique in database table.
     *
     * Format: unique:table,column,exceptId
     *
     * @param mixed $value
     * @param array $params [table, column, exceptId]
     * @param array $allData
     * @return bool|string
     */
    protected function validateUnique(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $table = $params[0] ?? $this->getTableNameFromModel();
        $column = $params[1] ?? null;
        $exceptId = $params[2] ?? null;

        // If no column specified, try to infer from field name in calling context
        if (!$column) {
            return "Unique rule requires column name.";
        }

        try {
            $query = "SELECT COUNT(*) as count FROM {$this->quoteIdentifier($table)} 
                     WHERE {$this->quoteIdentifier($column)} = ?";
            $bindings = [$value];

            // Exclude current record if updating
            if ($exceptId) {
                $primaryKey = $this->primary ?? 'id';
                $query .= " AND {$this->quoteIdentifier($primaryKey)} != ?";
                $bindings[] = $exceptId;
            }

            $result = db()->prepare($query)->execute($bindings)->fetch();

            if ($result['count'] > 0) {
                return "This value already exists.";
            }

            return true;

        } catch (\PDOException $e) {
            $this->logError("Unique validation failed: " . $e->getMessage());
            return "Could not verify uniqueness.";
        }
    }

    /**
     * In rule - value must be in the given list.
     *
     * @param mixed $value
     * @param array $params List of allowed values
     * @param array $allData
     * @return bool|string
     */
    protected function validateIn(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!in_array($value, $params)) {
            return "Must be one of: " . implode(', ', $params) . ".";
        }

        return true;
    }

    /**
     * Not in rule - value must not be in the given list.
     *
     * @param mixed $value
     * @param array $params List of disallowed values
     * @param array $allData
     * @return bool|string
     */
    protected function validateNotIn(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (in_array($value, $params)) {
            return "Must not be one of: " . implode(', ', $params) . ".";
        }

        return true;
    }

    /**
     * Regex rule - value must match the regular expression.
     *
     * @param mixed $value
     * @param array $params [pattern]
     * @param array $allData
     * @return bool|string
     */
    protected function validateRegex(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $pattern = $params[0] ?? '';

        if (!preg_match($pattern, $value)) {
            return "Format is invalid.";
        }

        return true;
    }

    /**
     * Alpha rule - value must contain only letters.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateAlpha(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $value)) {
            return "Must contain only letters.";
        }

        return true;
    }

    /**
     * Alphanumeric rule - value must contain only letters and numbers.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateAlphanumeric(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            return "Must contain only letters and numbers.";
        }

        return true;
    }

    /**
     * Date rule - value must be a valid date.
     *
     * @param mixed $value
     * @param array $params [format] Optional date format
     * @param array $allData
     * @return bool|string
     */
    protected function validateDate(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $format = $params[0] ?? 'Y-m-d';

        $date = \DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            return "Must be a valid date (format: {$format}).";
        }

        return true;
    }

    /**
     * Boolean rule - value must be boolean or boolean-like.
     *
     * @param mixed $value
     * @param array $params
     * @param array $allData
     * @return bool|string
     */
    protected function validateBoolean(mixed $value, array $params, array $allData): bool|string
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $valid = [true, false, 0, 1, '0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'];

        if (!in_array($value, $valid, true)) {
            return "Must be a boolean value.";
        }

        return true;
    }

    /**
     * Same rule - value must match another field.
     *
     * @param mixed $value
     * @param array $params [otherField]
     * @param array $allData
     * @return bool|string
     */
    protected function validateSame(mixed $value, array $params, array $allData): bool|string
    {
        $otherField = $params[0] ?? null;

        if (!$otherField) {
            return "Same rule requires field name.";
        }

        if ($value !== ($allData[$otherField] ?? null)) {
            return "Must match {$otherField}.";
        }

        return true;
    }

    /**
     * Different rule - value must be different from another field.
     *
     * @param mixed $value
     * @param array $params [otherField]
     * @param array $allData
     * @return bool|string
     */
    protected function validateDifferent(mixed $value, array $params, array $allData): bool|string
    {
        $otherField = $params[0] ?? null;

        if (!$otherField) {
            return "Different rule requires field name.";
        }

        if ($value === ($allData[$otherField] ?? null)) {
            return "Must be different from {$otherField}.";
        }

        return true;
    }
}
