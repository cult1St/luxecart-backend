<?php

namespace Helpers;

use Helpers\Utility;

class ErrorResponse
{
    public static function formatResponse($exception)
    {
        $errorMessage = $exception->getMessage();
        $lowerErrorMsg = strtolower(str_replace(["'", "`"], "", $errorMessage));

        // Detect DATABASE / SYSTEM errors
        $isSystemError =
            str_contains($lowerErrorMsg, "duplicate") ||
            str_contains($lowerErrorMsg, "unknown column") ||
            str_contains($lowerErrorMsg, "doesnt exist") ||
            str_contains($lowerErrorMsg, "doesnt have a default value") ||
            str_contains($lowerErrorMsg, "cannot be null") ||
            str_contains($lowerErrorMsg, "active transaction") ||
            str_contains($lowerErrorMsg, "could not resolve host") ||
            str_contains($lowerErrorMsg, "return value must be of");

        if ($isSystemError) {
            self::logger($exception, "fatal_error");
            $outputError = "An error occurred. Please contact support.";
        } else {
            // This is YOUR validation or business logic error
            self::logger($exception, "common_error");
            $outputError = $errorMessage;
        }

        http_response_code(400);
        return $outputError;
    }

    private static function logger($exception, $type = "common_error")
    {
        if ($type === "fatal_error") {
            Utility::log_txt('fatal_errors', $exception, 'logs');
        } else {
            Utility::log_txt('common_errors', $exception, 'logs');
        }
    }
}
