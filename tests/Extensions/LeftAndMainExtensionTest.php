<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Psr\Log\LoggerInterface;
use Restruct\Silverstripe\AdminTweaks\Extensions\LeftAndMainExtension;
use Restruct\Silverstripe\AdminTweaks\Tests\Stub\RecordingLogger;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Reports\ReportAdmin;

/**
 * Behavioural tests for the opt-in CMS menu hiding (admintweaks#54).
 *
 * Written against the observable contract - what `ignore_menuitem` ends up as, which is the
 * single value CMSMenu::menuitem_for_controller() consults - rather than against how the value
 * gets there, so the test keeps its meaning if the mechanism changes.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata, static
 * data providers only.
 */
class LeftAndMainExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function ignoreMenuitemFor(string $adminClass): bool
    {
        return (bool) Config::inst()->get($adminClass, 'ignore_menuitem');
    }

    public function testReportAdminIsVisibleByDefault()
    {
        // The headline of #54: up to 3.x this module hid ReportAdmin outright, so a project that
        // added its own Report subclass could not find it in the menu.
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', null);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertFalse(
            $this->ignoreMenuitemFor(ReportAdmin::class),
            'Reports must be visible when the project has not opted in to hiding it'
        );
    }

    public function testSectionsAreHiddenWhenExplicitlyOptedIn()
    {
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', true);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertTrue(
            $this->ignoreMenuitemFor(ReportAdmin::class),
            'Opting in must hide Reports'
        );
    }

    public function testSectionsStayVisibleWhenExplicitlyOptedOut()
    {
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', false);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertFalse($this->ignoreMenuitemFor(ReportAdmin::class));
    }

    public function testAProjectsOwnIgnoreMenuitemIsNotOverriddenWhenNotOptedIn()
    {
        // The other half of #54: a project setting ignore_menuitem itself must win, without
        // needing `After: '#admintweaks-leftandmain'` on its config fragment.
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', null);
        Config::modify()->set(ReportAdmin::class, 'ignore_menuitem', true);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertTrue(
            $this->ignoreMenuitemFor(ReportAdmin::class),
            "The module must not undo a project's own ignore_menuitem"
        );
    }

    public function testTheCoveredSectionListNamesBothSections()
    {
        $this->assertSame(
            [
                'SilverStripe\\Reports\\ReportAdmin',
                'SilverStripe\\CampaignAdmin\\CampaignAdmin',
            ],
            LeftAndMainExtension::RARELY_USED_ADMIN_SECTIONS
        );
    }

    // ------------------------------------------------- the upgrade notice

    public function testUndecidedProjectsGetADevNoticeAboutTheChangedDefault()
    {
        $logger = new RecordingLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', null);
        Config::modify()->set(Director::class, 'environment_type', 'dev');

        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertTrue(
            $logger->hasRecordContaining('hide_rarely_used_menu_sections'),
            'An undecided project must be told the default changed, and how to pin it'
        );
    }

    public function testDecidingEitherWaySilencesTheNotice()
    {
        foreach ([true, false] as $decision) {
            $logger = new RecordingLogger();
            Injector::inst()->registerService($logger, LoggerInterface::class);
            Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', $decision);
            Config::modify()->set(Director::class, 'environment_type', 'dev');

            LeftAndMainExtension::applyMenuVisibilityConfig();

            $this->assertCount(
                0,
                $logger->records,
                'Setting the config to ' . var_export($decision, true) . ' must silence the notice'
            );
        }
    }
}
