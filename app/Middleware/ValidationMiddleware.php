<?php

namespace App\Middleware;

use Core\Request;
use Core\Response;

/**
 * Validation Middleware
 * 
 * Validates request data
 */
class ValidationMiddleware
{
    protected Request $request;
    protected Response $response;
    protected array $rules = [];
    protected array $messages = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Set validation rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Set custom messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Validate request
     */
    public function validate(): bool
    {
        $errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $this->request->input($field);

            foreach (explode('|', $rules) as $rule) {
                $result = $this->validateRule($field, $value, $rule);
                if ($result !== true) {
                    $errors[$field][] = $result;
                }
            }
        }

        if (!empty($errors)) {
            $this->response->error('Validation failed', $errors, 422);
            return false;
        }

        return true;
    }

    /**
     * Validate single rule
     */
    protected function validateRule(string $field, $value, string $rule): bool|string
    {
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        } else {
            $param = null;
        }

        return match($rule) {
            'required' => empty($value) ? $this->getMessage($field, 'required') : true,
            'email' => !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) ? $this->getMessage($field, 'email') : true,
            'min' => strlen((string)$value) < (int)$param ? $this->getMessage($field, 'min', ['min' => $param]) : true,
            'max' => strlen((string)$value) > (int)$param ? $this->getMessage($field, 'max', ['max' => $param]) : true,
            'numeric' => !is_numeric($value) ? $this->getMessage($field, 'numeric') : true,
            'unique' => false, // Implement database unique check
            default => true,
        };
    }

    /**
     * Get validation message
     */
    protected function getMessage(string $field, string $rule, array $replace = []): string
    {
        $key = "{$field}.{$rule}";
        $message = $this->messages[$key] ?? "Validation failed for {$field}";

        foreach ($replace as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }

        return $message;
    }
}
