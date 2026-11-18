<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;

/**
 * add_tab_if_not_exists() must honour $insertAfter (admintweaks#145).
 */
class AddTabIfNotExistsTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function fields(): FieldList
    {
        return FieldList::create(
            TabSet::create('Root', Tab::create('Main'), Tab::create('Second'), Tab::create('Third'))
        );
    }

    private function tabNames(FieldList $fields): array
    {
        return array_map(function ($tab) {
            return $tab->getName();
        }, $fields->fieldByName('Root')->Tabs()->toArray());
    }

    public function testWithoutInsertAfterTheTabIsAppended(): void
    {
        $fields = $this->fields();
        GeneralHelpers::add_tab_if_not_exists($fields, 'Extra', 'Extra');

        $this->assertSame(['Main', 'Second', 'Third', 'Extra'], $this->tabNames($fields));
    }

    public function testInsertAfterIsHonoured(): void
    {
        // Before the fix any $insertAfter placed the tab after Main.
        $fields = $this->fields();
        GeneralHelpers::add_tab_if_not_exists($fields, 'Extra', 'Extra', 'Second');

        $this->assertSame(['Main', 'Second', 'Extra', 'Third'], $this->tabNames($fields));
    }

    public function testRootPrefixedNameGivesABareTabName(): void
    {
        // 'Root.Extra' and 'Extra' are the same tab; the tab must be named Extra, not Root.Extra.
        $fields = $this->fields();
        GeneralHelpers::add_tab_if_not_exists($fields, 'Root.Extra', 'Extra', 'Main');

        $this->assertSame(['Main', 'Extra', 'Second', 'Third'], $this->tabNames($fields));
        $this->assertSame('Extra', $fields->findTab('Root.Extra')->Title());
    }

    public function testAMissingInsertAfterTabAppends(): void
    {
        $fields = $this->fields();
        GeneralHelpers::add_tab_if_not_exists($fields, 'Extra', 'Extra', 'NoSuchTab');

        $this->assertSame(['Main', 'Second', 'Third', 'Extra'], $this->tabNames($fields));
    }

    public function testAnExistingTabIsLeftAlone(): void
    {
        $fields = $this->fields();
        GeneralHelpers::add_tab_if_not_exists($fields, 'Third', 'Renamed', 'Main');

        $this->assertSame(['Main', 'Second', 'Third'], $this->tabNames($fields));
    }
}
