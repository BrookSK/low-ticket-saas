<?php

namespace App\Core;

/**
 * Validador de entrada simples e extensivel.
 * Regras suportadas: required, email, min:n, max:n, numeric, integer,
 * confirmed, in:a,b,c, date, boolean, unique:tabela,coluna[,ignoreId].
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];

    /** @var array<string,string> */
    protected array $labels;

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
    }

    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_array($ruleset) ? $ruleset : explode('|', $ruleset);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }
        return $this->errors;
    }

    public function passes(): bool
    {
        return empty($this->validate());
    }

    protected function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    protected function applyRule(string $field, $value, string $name, ?string $param): void
    {
        $label = $this->label($field);
        $isEmpty = $value === null || $value === '';

        switch ($name) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, "O campo {$label} e obrigatorio.");
                }
                break;

            case 'email':
                if (!$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Informe um e-mail valido.");
                }
                break;

            case 'min':
                if (!$isEmpty && mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, "O campo {$label} deve ter no minimo {$param} caracteres.");
                }
                break;

            case 'max':
                if (!$isEmpty && mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, "O campo {$label} deve ter no maximo {$param} caracteres.");
                }
                break;

            case 'numeric':
                if (!$isEmpty && !is_numeric($value)) {
                    $this->addError($field, "O campo {$label} deve ser numerico.");
                }
                break;

            case 'integer':
                if (!$isEmpty && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "O campo {$label} deve ser um numero inteiro.");
                }
                break;

            case 'confirmed':
                $confirmation = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $confirmation) {
                    $this->addError($field, "A confirmacao de {$label} nao confere.");
                }
                break;

            case 'in':
                $options = explode(',', (string) $param);
                if (!$isEmpty && !in_array((string) $value, $options, true)) {
                    $this->addError($field, "Valor invalido para {$label}.");
                }
                break;

            case 'date':
                if (!$isEmpty && strtotime((string) $value) === false) {
                    $this->addError($field, "O campo {$label} deve ser uma data valida.");
                }
                break;

            case 'boolean':
                if (!$isEmpty && !in_array((string) $value, ['0', '1', 'true', 'false', 'on'], true)) {
                    $this->addError($field, "O campo {$label} deve ser booleano.");
                }
                break;

            case 'unique':
                if (!$isEmpty) {
                    [$table, $column, $ignoreId] = array_pad(explode(',', (string) $param), 3, null);
                    $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?";
                    $bindings = [$value];
                    if ($ignoreId !== null) {
                        $sql .= " AND id <> ?";
                        $bindings[] = $ignoreId;
                    }
                    $row = app(Database::class)->selectOne($sql, $bindings);
                    if ((int) ($row['c'] ?? 0) > 0) {
                        $this->addError($field, "Este {$label} ja esta em uso.");
                    }
                }
                break;
        }
    }
}
