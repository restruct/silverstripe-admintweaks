<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Dev;

use Restruct\Silverstripe\AdminTweaks\Dev\OrmQueryTask;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Console\Input\InputOption;

/**
 * Tests for OrmQueryTask's option parsing.
 *
 * The one that matters is admintweaks#60: on the 3.x line two `filter[...]` CLI arguments silently
 * collapsed to the last one, because framework's CLIRequestBuilder parses each argument in
 * isolation and array_merge()s the results, which overwrites string keys. A query that looked
 * filtered was filtered by one condition, and the reassuringly small number was wrong.
 *
 * This line cannot reproduce that: SS6 tasks are symfony/console commands, and `--filter` is
 * declared VALUE_IS_ARRAY, so repeats collect instead of overwriting. These tests pin that
 * property so a future change to the option declaration cannot quietly reintroduce it.
 */
class OrmQueryTaskTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function optionNamed(string $name): InputOption
    {
        foreach (OrmQueryTask::singleton()->getOptions() as $option) {
            if ($option->getName() === $name) {
                return $option;
            }
        }

        $this->fail("OrmQueryTask declares no --{$name} option");
    }

    public function testFilterIsRepeatableSoTwoFiltersBothApply()
    {
        // admintweaks#60. Without VALUE_IS_ARRAY the second --filter replaces the first and the
        // query is silently narrower than asked for.
        $this->assertTrue(
            $this->optionNamed('filter')->isArray(),
            '--filter must be repeatable, or two filters silently collapse to one'
        );
    }

    public function testExcludeIsRepeatableForTheSameReason()
    {
        $this->assertTrue($this->optionNamed('exclude')->isArray());
    }

    public function testBothFiltersSurviveParsing()
    {
        // The parsing half: two pairs in, two pairs out, and the first is not lost.
        $task = OrmQueryTask::singleton();
        $parse = new \ReflectionMethod($task, 'parsePairs');
        $parse->setAccessible(true);

        $parsed = $parse->invoke($task, ['Type=OPL-Vakmodule', 'IsActive=0']);

        $this->assertSame(['Type' => 'OPL-Vakmodule', 'IsActive' => '0'], $parsed);
    }

    public function testAValueContainingAnEqualsSignIsNotTruncated()
    {
        $task = OrmQueryTask::singleton();
        $parse = new \ReflectionMethod($task, 'parsePairs');
        $parse->setAccessible(true);

        // Split on the FIRST '=' only - a value may legitimately contain more.
        $this->assertSame(
            ['Query' => 'a=b&c=d'],
            $parse->invoke($task, ['Query=a=b&c=d'])
        );
    }

    public function testOperatorSyntaxSurvivesParsing()
    {
        $task = OrmQueryTask::singleton();
        $parse = new \ReflectionMethod($task, 'parsePairs');
        $parse->setAccessible(true);

        $this->assertSame(
            ['Email:PartialMatch' => 'example'],
            $parse->invoke($task, ['Email:PartialMatch=example'])
        );
    }
}
