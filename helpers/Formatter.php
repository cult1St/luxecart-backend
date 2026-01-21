<?php

namespace Helpers;

/**
 * Formatter Helper
 * 
 * Provides formatting utilities
 */
class Formatter
{
    /**
     * Format currency
     */
    public static function currency(float $amount, string $currency = 'USD'): string
    {
        return match($currency) {
            'USD' => '$' . number_format($amount, 2),
            'EUR' => '€' . number_format($amount, 2, ',', '.'),
            'GBP' => '£' . number_format($amount, 2),
            'NGN' => '₦' . number_format($amount, 2),
            default => $currency . ' ' . number_format($amount, 2),
        };
    }

    /**
     * Format date
     */
    public static function date(string $date, string $format = 'M d, Y'): string
    {
        return date($format, strtotime($date));
    }

    /**
     * Format date time
     */
    public static function dateTime(string $dateTime, string $format = 'M d, Y H:i'): string
    {
        return date($format, strtotime($dateTime));
    }

    /**
     * Format file size
     */
    public static function fileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Truncate text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Generate slug
     */
    public static function slug(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^\w\s-]/', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        return preg_replace('/[\-]+/', '-', $text);
    }

    /**
     * Escape HTML
     */
    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format phone number
     */
    public static function phone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);
        if (strlen($phone) === 10) {
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $phone;
    }

    /**
     * Get initials from name
     */
    public static function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        return strtoupper(substr($parts[0], 0, 1)) . (count($parts) > 1 ? strtoupper(substr($parts[1], 0, 1)) : '');
    }
}
