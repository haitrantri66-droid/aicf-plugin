<?php
namespace AICF\AI\Exceptions;

if (!defined('ABSPATH')) {
    exit;
}

class AIException extends \Exception {
    private $is_retryable;

    public function __construct($message, $code = 0, $is_retryable = false, \Throwable $previous = null) {
        $this->is_retryable = $is_retryable;
        parent::__construct($message, $code, $previous);
    }

    public function is_retryable() {
        return $this->is_retryable;
    }
}