<?php

class Validator
{
    private mixed $value;
    private $error_array = [];
    private bool $lastValidationFailed = false;

    function __construct(mixed $value)
    {
        $this->value = $value;
    }

    function toInt()
    {
        if (!is_numeric($this->value))
            throw new Error("Can't Sanitize the value");

        $this->value = (int)$this->value;

        return $this;
    }

    function isInt(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX)
    {
        if (!is_int($this->value) && !is_numeric($this->value)) {
            $this->error_array[] = $this->value . ' Type is NOT INT';
            $this->lastValidationFailed = true;
            return $this;
        }

        if ($this->value < $min) {
            $this->error_array[] = $this->value . ' is less than min';
            $this->lastValidationFailed = true;
        }

        if ($this->value > $max) {
            $this->error_array[] = $this->value . ' is greater than max';
            $this->lastValidationFailed = true;
        }

        return $this;
    }

    function isString(int $min = -1, int $max = 2084)
    {
        if (!is_string($this->value)) {
            $this->error_array[] = $this->value . ' Type is NOT String';
            $this->lastValidationFailed = true;
            return $this;
        }

        if (strlen($this->value) < $min) {
            $this->error_array[] = $this->value . 'Length is less than min';
            $this->lastValidationFailed = true;
        }

        if (strlen($this->value) > $max) {
            $this->error_array[] = $this->value . 'Length is greater than max';
            $this->lastValidationFailed = true;
        }

        return $this;
    }

    function isDouble()
    {
        if (!is_double($this->value) && !is_numeric($this->value)) {
            $this->error_array[] = $this->value . ' Type is NOT Double';
            $this->lastValidationFailed = true;
        }
        return $this;
    }

    function toDouble()
    {
        if (!is_numeric($this->value))
            throw new Error("Can't Sanitize the value");

        $this->value = (float)$this->value;

        return $this;
    }

    function withMessage(string $message)
    {
        if ($this->lastValidationFailed && count($this->error_array))
            $this->error_array[count($this->error_array) - 1] = $message;

        return $this;
    }

    function getErrors()
    {
        return $this->error_array;
    }

    function getValue()
    {
        return $this->value;
    }
}
