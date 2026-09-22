<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use Psr\Log\AbstractLogger;
use SilverStripe\Dev\TestOnly;

/**
 * Minimal recording logger.
 *
 * psr/log dropped Psr\Log\Test\TestLogger in 3.0, and SS6 ships psr/log 3 - a test skipping on
 * its absence would simply never run.
 */
class RecordingLogger extends AbstractLogger implements TestOnly
{
    /** @var array<int, array{level: mixed, message: string}> */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message];
    }

    public function hasRecordContaining(string $needle): bool
    {
        foreach ($this->records as $record) {
            if (str_contains($record['message'], $needle)) {
                return true;
            }
        }

        return false;
    }
}
