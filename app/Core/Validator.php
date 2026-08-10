<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple validation engine.
 *
 * Supported rules:
 *  required, nullable, email, numeric, integer, boolean, string,
 *  min:value, max:value, decimal, date, in:a,b,c, regex:pattern,
 *  unique:table,column,ignoreId, exists:table,column, confirmed
 */
class Validator
{
    private array $errors = [];
    private array $messages = [
        'required' => 'The %s field is required.',
        'email' => 'The %s must be a valid email address.',
        'numeric' => 'The %s must be a number.',
        'integer' => 'The %s must be a whole number.',
        'boolean' => 'The %s must be true or false.',
        'string' => 'The %s must be text.',
        'min' => 'The %s must be at least :param.',
        'max' => 'The %s must not exceed :param.',
        'decimal' => 'The %s must be a valid amount.',
        'date' => 'The %s must be a valid date.',
        'in' => 'The %s must be one of: :param.',
        'unique' => 'The %s has already been taken.',
        'exists' => 'The selected %s is invalid.',
        'confirmed' => 'The %s confirmation does not match.',
        'regex' => 'The %s format is invalid.',
    ];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            if (is_array($ruleString)) {
                $ruleList = $ruleString;
            } else {
                $ruleList = explode('|', (string) $ruleString);
            }

            $value = $data[$field] ?? null;
            $label = ucwords(str_replace('_', ' ', $field));

            $required = in_array('required', $ruleList, true);
            $nullable = in_array('nullable', $ruleList, true);
            $empty = $value === null || $value === '';

            if ($empty) {
                if ($required) {
                    $this->addError($field, sprintf($this->messages['required'], $label));
                }
                continue;
            }
            if ($nullable && $empty) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if (in_array($rule, ['required', 'nullable'], true)) {
                    continue;
                }
                $this->applyRule($field, $label, $rule, $value, $data, $ruleList);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, string $label, string $rule, $value, array $data, array $ruleList): void
    {
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        switch ($ruleName) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, sprintf($this->messages['email'], $label));
                }
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, sprintf($this->messages['numeric'], $label));
                }
                break;
            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, sprintf($this->messages['integer'], $label));
                }
                break;
            case 'boolean':
                if (!in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true)) {
                    $this->addError($field, sprintf($this->messages['boolean'], $label));
                }
                break;
            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, sprintf($this->messages['string'], $label));
                }
                break;
            case 'min':
                if ($this->isNumericField($ruleList)) {
                    if (is_numeric($value) && (float) $value < (float) $param) {
                        $this->addError($field, $this->messageWithParam('min', $param, $label));
                    }
                } elseif (is_string($value) && mb_strlen($value) < (int) $param) {
                    $this->addError($field, $this->messageWithParam('min', $param, $label));
                }
                break;
            case 'max':
                if ($this->isNumericField($ruleList)) {
                    if (is_numeric($value) && (float) $value > (float) $param) {
                        $this->addError($field, $this->messageWithParam('max', $param, $label));
                    }
                } elseif (is_string($value) && mb_strlen($value) > (int) $param) {
                    $this->addError($field, $this->messageWithParam('max', $param, $label));
                }
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    $this->addError($field, sprintf($this->messages['decimal'], $label));
                }
                break;
            case 'date':
                if (strtotime((string) $value) === false) {
                    $this->addError($field, sprintf($this->messages['date'], $label));
                }
                break;
            case 'in':
                $allowed = array_map('trim', explode(',', (string) $param));
                if (!in_array($value, $allowed, false)) {
                    $message = str_replace(':param', implode(', ', $allowed), $this->messages['in']);
                    $this->addError($field, sprintf($message, $label));
                }
                break;
            case 'confirmed':
                $confirmation = $data[$field . '_confirmation'] ?? null;
                if ($confirmation !== $value) {
                    $this->addError($field, sprintf($this->messages['confirmed'], $label));
                }
                break;
            case 'regex':
                if (@preg_match($param, (string) $value) !== 1) {
                    $this->addError($field, sprintf($this->messages['regex'], $label));
                }
                break;
            case 'unique':
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? null;
                $column = $parts[1] ?? 'id';
                $ignoreId = $parts[2] ?? null;
                if ($table) {
                    $db = App::getInstance()->db;
                    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?" . $this->softDeleteClause($table);
                    $params = [$value];
                    if ($ignoreId !== null && $ignoreId !== '') {
                        $sql .= " AND `id` != ?";
                        $params[] = (int) $ignoreId;
                    }
                    if ($db->count($sql, $params) > 0) {
                        $this->addError($field, sprintf($this->messages['unique'], $label));
                    }
                }
                break;
            case 'exists':
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? null;
                $column = $parts[1] ?? 'id';
                if ($table) {
                    $db = App::getInstance()->db;
                    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?" . $this->softDeleteClause($table);
                    if ($db->count($sql, [$value]) === 0) {
                        $this->addError($field, sprintf($this->messages['exists'], $label));
                    }
                }
                break;
        }
    }

    /**
     * Whether the given table has a soft-delete (deleted_at) column.
     * Falls back to checking information_schema; tables without the
     * column (e.g. branches) skip the soft-delete filter.
     */
    private function softDeleteClause(string $table): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return '';
        }
        try {
            $db = App::getInstance()->db;
            $row = $db->query("SHOW COLUMNS FROM `$table` LIKE 'deleted_at'")->fetch();
            return $row ? ' AND `deleted_at` IS NULL' : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function isNumericField(array $ruleList): bool
    {
        return in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);
    }

    private function messageWithParam(string $ruleName, $param, string $label): string
    {
        return sprintf(str_replace(':param', $param, $this->messages[$ruleName]), $label);
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function errorsFlat(): array
    {
        $flat = [];
        foreach ($this->errors as $field => $messages) {
            $flat[$field] = implode(' ', $messages);
        }
        return $flat;
    }

    public static function make(array $data, array $rules): array
    {
        $validator = new self();
        $validator->validate($data, $rules);
        return $validator->errorsFlat();
    }
}
