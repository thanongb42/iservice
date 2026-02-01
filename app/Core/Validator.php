<?php

namespace App\Core;

/**
 * Validator Class
 * จัดการ input validation
 */
class Validator
{
    private $data;
    private $rules;
    private $errors = [];

    public function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Create validator instance
     */
    public static function make($data, $rules)
    {
        $validator = new self($data, $rules);
        $validator->validate();

        return [
            'valid' => empty($validator->errors),
            'errors' => $validator->errors
        ];
    }

    /**
     * Validate data against rules
     */
    private function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $rulesList = is_string($rules) ? explode('|', $rules) : $rules;

            foreach ($rulesList as $rule) {
                $this->applyRule($field, $rule);
            }
        }
    }

    /**
     * Apply validation rule
     */
    private function applyRule($field, $rule)
    {
        // Parse rule and parameters
        if (strpos($rule, ':') !== false) {
            list($ruleName, $params) = explode(':', $rule, 2);
            $params = explode(',', $params);
        } else {
            $ruleName = $rule;
            $params = [];
        }

        $value = $this->data[$field] ?? null;

        // Apply validation
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'กรุณากรอก ' . $field);
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'รูปแบบอีเมลไม่ถูกต้อง');
                }
                break;

            case 'min':
                $min = $params[0] ?? 0;
                if (!empty($value) && strlen($value) < $min) {
                    $this->addError($field, $field . ' ต้องมีอย่างน้อย ' . $min . ' ตัวอักษร');
                }
                break;

            case 'max':
                $max = $params[0] ?? 0;
                if (!empty($value) && strlen($value) > $max) {
                    $this->addError($field, $field . ' ต้องไม่เกิน ' . $max . ' ตัวอักษร');
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, $field . ' ต้องเป็นตัวเลข');
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, $field . ' ต้องเป็นจำนวนเต็ม');
                }
                break;

            case 'between':
                $min = $params[0] ?? 0;
                $max = $params[1] ?? 0;
                if (!empty($value) && (intval($value) < $min || intval($value) > $max)) {
                    $this->addError($field, $field . ' ต้องอยู่ระหว่าง ' . $min . ' ถึง ' . $max);
                }
                break;

            case 'unique':
                $table = $params[0] ?? null;
                $column = $params[1] ?? $field;
                $excludeId = $params[2] ?? null;

                if ($table && !empty($value)) {
                    $db = \App\Config\Database::getConnection();

                    if ($excludeId) {
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ? AND id != ?");
                        $stmt->execute([$value, $excludeId]);
                    } else {
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
                        $stmt->execute([$value]);
                    }

                    $result = $stmt->fetch();
                    if ($result['count'] > 0) {
                        $this->addError($field, $field . ' นี้ถูกใช้งานแล้ว');
                    }
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;

                if ($value !== $confirmValue) {
                    $this->addError($field, $field . ' ไม่ตรงกัน');
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'รูปแบบ URL ไม่ถูกต้อง');
                }
                break;

            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    $this->addError($field, $field . ' ต้องเป็นตัวอักษรเท่านั้น');
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum($value)) {
                    $this->addError($field, $field . ' ต้องเป็นตัวอักษรและตัวเลขเท่านั้น');
                }
                break;
        }
    }

    /**
     * Add error message
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !$this->passes();
    }
}
