<?php

// NOTE: SS4 date/time formatting: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBDatetime;

class DBTimeExtension
    extends Extension
{
    public function LegacyFormat($formatString)
    {
        return date($formatString, $this->owner->getTimestamp());
    }

    /**
     * Return seconds (since start of day instead of since unix epoch)
     *
     * @return int
     */
    public function AsSeconds()
    {
        return $this->owner->getTimestamp() - strtotime('0:00:00');
    }

    /**
     * Format an amount of seconds using $formaString
     * (makes static method callable from templates)
     *
     * @param int $seconds eg result of AsSeconds()
     * @param string $formaString (default 'H:mm') see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     * @return false|string
     */
    public function FormatSecondsAsTime($seconds, $formaString = 'H:mm')
    {
        return self::format_seconds_as_time($seconds, $formaString);
    }

    /**
     * Format an amount of seconds using $formatString
     *
     * @param int $seconds eg result of AsSeconds()
     * @param string $formaString (default 'H:mm') see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     * @return false|string
     */
    public static function format_seconds_as_time($seconds, $formatString = 'H:mm')
    {
        /** @var DBTime $this->owner */
//        return date($formatString, strtotime("1-1-1970 0:00:00") + $seconds);
        $formatter = \IntlDateFormatter::create(\SilverStripe\i18n\i18n::get_locale(), \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM);
        $formatter->setPattern($formatString);
        return $formatter->format(strtotime("1-1-1970 0:00:00") + $seconds);
    }

    //
    // Duplicate some nice functionality from DBDate/DBDatetime to DBTime...
    //

    /**
     * Returns the time in 12-hour format using the format string 'h:mm a' e.g. '1:32 pm'.
     *
     * @return string Formatted time.
     */
    public function Time12($includeSeconds = false)
    {
        $secondsFormat = $includeSeconds ? ':ss' : '';
        return $this->owner->Format("h:mm{$secondsFormat} a");
    }

    /**
     * Returns the time in 24-hour format using the format string 'H:mm' e.g. '13:32'.
     *
     * @return string Formatted time.
     */
    public function Time24($includeSeconds = false)
    {
        $secondsFormat = $includeSeconds ? ':ss' : '';
        return $this->owner->Format("H:mm{$secondsFormat}");
    }

    /**
     * Returns the number of seconds/minutes/hours/days or months since the timestamp.
     *
     * @param boolean $includeSeconds Show seconds, or just round to "less than a minute".
     * @param int $significance Minimum significant value of X for "X units ago" to display
     * @return string
     */
    public function Ago($includeSeconds = true, $significance = 2)
    {
        if (!$this->owner->value) {
            return null;
        }
        $timestamp = $this->owner->getTimestamp();
        $now = DBDatetime::now()->getTimestamp();
        if ($timestamp <= $now) {
            return _t(
                __CLASS__ . '.TIMEDIFFAGO',
                "{difference} ago",
                'Natural language time difference, e.g. 2 hours ago',
                ['difference' => $this->owner->TimeDiff($includeSeconds, $significance)]
            );
        }
        return _t(
            __CLASS__ . '.TIMEDIFFIN',
            "in {difference}",
            'Natural language time difference, e.g. in 2 hours',
            ['difference' => $this->owner->TimeDiff($includeSeconds, $significance)]
        );
    }

    /**
     * @param boolean $includeSeconds Show seconds, or just round to "less than a minute".
     * @param int $significance Minimum significant value of X for "X units ago" to display
     * @return string
     */
    public function TimeDiff($includeSeconds = true, $significance = 2)
    {
        if (!$this->owner->value) {
            return false;
        }

        $now = DBDatetime::now()->getTimestamp();
        $time = $this->owner->getTimestamp();
        $ago = abs($time - $now);
        if ($ago < 60 && !$includeSeconds) {
            return _t(DBDatetime::class . '.LessThanMinuteAgo', 'less than a minute');
        }
        if ($ago < $significance * 60 && $includeSeconds) {
            return $this->owner->TimeDiffIn('seconds');
        }
        if ($ago < $significance * 3600) {
            return $this->owner->TimeDiffIn('minutes');
        }
        if ($ago < $significance * 86400) {
            return $this->owner->TimeDiffIn('hours');
        }
        if ($ago < $significance * 86400 * 30) {
            return $this->owner->TimeDiffIn('days');
        }
        if ($ago < $significance * 86400 * 365) {
            return $this->owner->TimeDiffIn('months');
        }
        return $this->owner->TimeDiffIn('years');
    }

    /**
     * Gets the time difference, but always returns it in a certain format
     *
     * @param string $format The format, could be one of these:
     * 'seconds', 'minutes', 'hours', 'days', 'months', 'years'.
     * @return string The resulting formatted period
     */
    public function TimeDiffIn($format)
    {
        if (!$this->owner->value) {
            return null;
        }

        $now = DBDatetime::now()->getTimestamp();
        $time = $this->owner->getTimestamp();
        $ago = abs($time - $now);
        switch ($format) {
            case 'seconds':
                $span = $ago;
                return _t(
                    DBDatetime::class . '.SECONDS_SHORT_PLURALS',
                    '{count} sec|{count} secs',
                    ['count' => $span]
                );

            case 'minutes':
                $span = round($ago / 60);
                return _t(
                    DBDatetime::class . '.MINUTES_SHORT_PLURALS',
                    '{count} min|{count} mins',
                    ['count' => $span]
                );

            case 'hours':
                $span = round($ago / 3600);
                return _t(
                    DBDatetime::class . '.HOURS_SHORT_PLURALS',
                    '{count} hour|{count} hours',
                    ['count' => $span]
                );

            case 'days':
                $span = round($ago / 86400);
                return _t(
                    DBDatetime::class . '.DAYS_SHORT_PLURALS',
                    '{count} day|{count} days',
                    ['count' => $span]
                );

            case 'months':
                $span = round($ago / 86400 / 30);
                return _t(
                    DBDatetime::class . '.MONTHS_SHORT_PLURALS',
                    '{count} month|{count} months',
                    ['count' => $span]
                );

            case 'years':
                $span = round($ago / 86400 / 365);
                return _t(
                    DBDatetime::class . '.YEARS_SHORT_PLURALS',
                    '{count} year|{count} years',
                    ['count' => $span]
                );

            default:
                throw new \InvalidArgumentException("Invalid format $format");
        }
    }

}
