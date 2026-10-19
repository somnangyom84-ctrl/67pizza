<?php
declare(strict_types=1);

/** Trim + strip control chars, limit length. */
function clean_string(mixed $value, int $max = 255): string
{
    $value = is_string($value) ? $value : '';
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    $value = trim($value);
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 190;
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+?[0-9\s\-]{8,20}$/', $phone);
}

function to_int(mixed $value, int $default = 0): int
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
}

function to_decimal(mixed $value, float $default = 0.0): float
{
    if (is_string($value)) {
        $value = str_replace([',', ' '], '', $value);
    }
    return is_numeric($value) ? round((float) $value, 2) : $default;
}

/**
 * Lightweight validator.
 * $rules = ['name' => 'required|min:2|max:120', 'email' => 'required|email']
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules, array $labels = []): bool
    {
        foreach ($rules as $field => $ruleString) {
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $value = $this->data[$field] ?? null;
            $valueStr = is_scalar($value) ? trim((string) $value) : '';
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                switch ($name) {
                    case 'required':
                        if ($valueStr === '') { $this->add($field, "$label is required."); break 2; }
                        break;
                    case 'email':
                        if ($valueStr !== '' && !valid_email($valueStr)) { $this->add($field, "$label must be a valid email address."); }
                        break;
                    case 'phone':
                        if ($valueStr !== '' && !valid_phone($valueStr)) { $this->add($field, "$label must be a valid phone number."); }
                        break;
                    case 'min':
                        if ($valueStr !== '' && mb_strlen($valueStr) < (int) $param) { $this->add($field, "$label must be at least $param characters."); }
                        break;
                    case 'max':
                        if (mb_strlen($valueStr) > (int) $param) { $this->add($field, "$label may not exceed $param characters."); }
                        break;
                    case 'numeric':
                        if ($valueStr !== '' && !is_numeric(str_replace(',', '', $valueStr))) { $this->add($field, "$label must be a number."); }
                        break;
                    case 'integer':
                        if ($valueStr !== '' && filter_var($valueStr, FILTER_VALIDATE_INT) === false) { $this->add($field, "$label must be a whole number."); }
                        break;
                    case 'min_value':
                        if ($valueStr !== '' && is_numeric($valueStr) && (float) $valueStr < (float) $param) { $this->add($field, "$label must be at least $param."); }
                        break;
                    case 'in':
                        $allowed = explode(',', (string) $param);
                        if ($valueStr !== '' && !in_array($valueStr, $allowed, true)) { $this->add($field, "$label has an invalid value."); }
                        break;
                    case 'confirmed':
                        $confirm = $this->data[$field . '_confirmation'] ?? '';
                        if ($valueStr !== (string) $confirm) { $this->add($field, "$label confirmation does not match."); }
                        break;
                    case 'password':
                        if ($valueStr !== '' && (mb_strlen($valueStr) < 8 || !preg_match('/[A-Za-z]/', $valueStr) || !preg_match('/[0-9]/', $valueStr))) {
                            $this->add($field, "$label must be at least 8 characters and contain letters and numbers.");
                        }
                        break;
                }
            }
        }
        return empty($this->errors);
    }

    private function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstErrors(): array
    {
        return array_map(fn($list) => $list[0], $this->errors);
    }

    public function allMessages(): array
    {
        return array_merge(...array_values($this->errors ?: [[]]));
    }
}
