<?php

declare(strict_types=1);

namespace App\Utility;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

class DateHelper
{
    public const DB_FORMAT = 'Y-m-d';
    public const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const DISPLAY_FORMAT = 'd-m-Y';
    public const DISPLAY_DATETIME_FORMAT = 'd-m-Y H:i:s';
    public const HUMAN_FORMAT = 'M j, Y';

    public static function now(): string
    {
        return date(self::DB_DATETIME_FORMAT);
    }

    public static function today(): string
    {
        return date(self::DB_FORMAT);
    }

    public static function format(string $dateStr, string $format = self::DISPLAY_FORMAT): string
    {
        if (empty($dateStr)) {
            return '';
        }
        try {
            $dt = new DateTime($dateStr);
            return $dt->format($format);
        } catch (Throwable) {
            return $dateStr;
        }
    }

    public static function formatDate(?string $dateStr): string
    {
        return self::format((string)$dateStr, self::DISPLAY_FORMAT);
    }

    public static function formatDateTime(?string $dateStr): string
    {
        return self::format((string)$dateStr, self::DISPLAY_DATETIME_FORMAT);
    }

    public static function toDb(?string $dateStr, string $inputFormat = self::DISPLAY_FORMAT): ?string
    {
        if (empty($dateStr)) {
            return null;
        }
        try {
            $dt = DateTime::createFromFormat($inputFormat, $dateStr);
            if ($dt instanceof DateTime) {
                return $dt->format(self::DB_FORMAT);
            }
            return $dateStr;
        } catch (Throwable) {
            return $dateStr;
        }
    }

    public static function toDbDateTime(?string $dateStr, string $inputFormat = self::DISPLAY_DATETIME_FORMAT): ?string
    {
        if (empty($dateStr)) {
            return null;
        }
        try {
            $dt = DateTime::createFromFormat($inputFormat, $dateStr);
            if ($dt instanceof DateTime) {
                return $dt->format(self::DB_DATETIME_FORMAT);
            }
            return $dateStr;
        } catch (Throwable) {
            return $dateStr;
        }
    }

    public static function diffForHumans(string $dateStr): string
    {
        try {
            $now = new DateTimeImmutable();
            $dt = new DateTimeImmutable($dateStr);
            $diff = $now->diff($dt);

            $isPast = $dt < $now;

            if ($diff->y > 0) {
                return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ($isPast ? ' ago' : ' from now');
            }
            if ($diff->m > 0) {
                return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ($isPast ? ' ago' : ' from now');
            }
            if ($diff->d > 0) {
                return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ($isPast ? ' ago' : ' from now');
            }
            if ($diff->h > 0) {
                return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ($isPast ? ' ago' : ' from now');
            }
            if ($diff->i > 0) {
                return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ($isPast ? ' ago' : ' from now');
            }
            return 'just now';
        } catch (Throwable) {
            return $dateStr;
        }
    }

    public static function isExpired(string $dateStr): bool
    {
        try {
            $now = new DateTimeImmutable();
            $dt = new DateTimeImmutable($dateStr);
            return $dt < $now;
        } catch (Throwable) {
            return false;
        }
    }

    public static function daysBetween(string $from, string $to): int
    {
        try {
            $fromDt = new DateTimeImmutable($from);
            $toDt = new DateTimeImmutable($to);
            return (int) $fromDt->diff($toDt)->days;
        } catch (Throwable) {
            return 0;
        }
    }

    public static function addDays(string $dateStr, int $days, string $format = self::DB_FORMAT): string
    {
        try {
            $dt = new DateTime($dateStr);
            $dt->modify(($days >= 0 ? '+' : '') . $days . ' days');
            return $dt->format($format);
        } catch (Throwable) {
            return $dateStr;
        }
    }

    public static function startOfMonth(?string $dateStr = null): string
    {
        try {
            $dt = new DateTime($dateStr ?? 'now');
            return $dt->format('Y-m-01');
        } catch (Throwable) {
            return date('Y-m-01');
        }
    }

    public static function endOfMonth(?string $dateStr = null): string
    {
        try {
            $dt = new DateTime($dateStr ?? 'now');
            return $dt->format('Y-m-t');
        } catch (Throwable) {
            return date('Y-m-t');
        }
    }

    public static function isValid(string $dateStr, string $format = self::DB_FORMAT): bool
    {
        try {
            $dt = DateTime::createFromFormat($format, $dateStr);
            return $dt instanceof DateTime && $dt->format($format) === $dateStr;
        } catch (Throwable) {
            return false;
        }
    }

    public static function age(?string $dateOfBirth): int
    {
        if (empty($dateOfBirth)) {
            return 0;
        }
        try {
            $dob = new DateTimeImmutable($dateOfBirth);
            $now = new DateTimeImmutable();
            return (int) $dob->diff($now)->y;
        } catch (Throwable) {
            return 0;
        }
    }
}
