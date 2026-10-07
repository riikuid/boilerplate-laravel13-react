<?php

namespace App\Support;

use Inertia\Inertia;

/**
 * One-shot toast shown by the `useFlashToast` hook on the next Inertia response.
 */
final class Flash
{
    public static function success(string $message): void
    {
        self::toast('success', $message);
    }

    public static function error(string $message): void
    {
        self::toast('error', $message);
    }

    public static function info(string $message): void
    {
        self::toast('info', $message);
    }

    public static function warning(string $message): void
    {
        self::toast('warning', $message);
    }

    private static function toast(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
