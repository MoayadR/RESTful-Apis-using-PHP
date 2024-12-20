<?php

function getAllErrorsFromValidator(...$validators)
{
    $errors = [];
    foreach ($validators as $validator) {
        $value = $validator->name;

        $subErrors = $validator->getErrors();

        if (count($subErrors)) {
            $errors[$value] =  $subErrors;
        }
    }
    return $errors;
}

class Validator
{
    public mixed $value;
    private $error_array = [];
    private bool $lastValidationFailed = false;

    public string $name;

    function __construct(mixed $value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
    }

    function notEmpty()
    {
        if (is_null($this->value)) {
            $this->error_array[] = $this->name . ' Is Empty';
            $this->lastValidationFailed = true;
        }
        return $this;
    }

    function toInt()
    {
        if (is_null($this->value))
            return $this;

        if (!is_numeric($this->value))
            return $this;

        $this->value = (int)$this->value;

        return $this;
    }

    function isInt(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX)
    {
        if (is_null($this->value))
            return $this;

        if (!is_int($this->value) && !is_numeric($this->value)) {
            $this->error_array[] = $this->name . ' Type is NOT INT';
            $this->lastValidationFailed = true;
            return $this;
        }

        if ($this->value < $min) {
            $this->error_array[] = $this->name . ' is less than min';
            $this->lastValidationFailed = true;
        }

        if ($this->value > $max) {
            $this->error_array[] = $this->name . ' is greater than max';
            $this->lastValidationFailed = true;
        }

        return $this;
    }

    function isString(int $min = -1, int $max = 2084)
    {
        if (is_null($this->value))
            return $this;

        if (!is_string($this->value)) {
            $this->error_array[] = $this->name . ' Type is NOT String';
            $this->lastValidationFailed = true;
            return $this;
        }

        if (strlen($this->value) < $min) {
            $this->error_array[] = $this->name . 'Length is less than min';
            $this->lastValidationFailed = true;
        }

        if (strlen($this->value) > $max) {
            $this->error_array[] = $this->name . 'Length is greater than max';
            $this->lastValidationFailed = true;
        }

        return $this;
    }

    function isDouble()
    {
        if (is_null($this->value))
            return $this;

        if (!is_double($this->value) && !is_numeric($this->value)) {
            $this->error_array[] = $this->name . ' Type is NOT Double';
            $this->lastValidationFailed = true;
        }
        return $this;
    }

    function toDouble()
    {
        if (is_null($this->value))
            return $this;

        if (!is_numeric($this->value))
            return $this;

        $this->value = (float)$this->value;

        return $this;
    }

    function withMessage(string $message)
    {
        if ($this->lastValidationFailed && count($this->error_array)) {
            $this->error_array[count($this->error_array) - 1] = $message;
            $this->lastValidationFailed = false;
        }

        return $this;
    }

    function toString()
    {
        if (is_null($this->value))
            return $this;

        $this->value = (string) $this->value;

        return $this;
    }

    function getErrors()
    {
        return $this->error_array;
    }
}
