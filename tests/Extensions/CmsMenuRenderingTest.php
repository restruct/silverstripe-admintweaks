<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Restruct\Silverstripe\AdminTweaks\Extensions\LeftAndMainExtension;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;

/**
 * End-to-end check that the opt-in menu hiding (admintweaks#54) reaches the rendered CMS.
 *
 * The unit tests alongside this assert the resolved `ignore_menuitem` value. That is the value
 * CMSMenu consults, but it is still one step short of the thing the issue is actually about:
 * whether the menu item appears. This drives the real admin controller and reads the markup, so
 * a future change in how CMSMenu consumes that config cannot pass silently.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6).
 */
class CmsMenuRenderingTest extends FunctionalTest
{
    /**
     * Required. Without it SapphireTest builds no temporary database and the test runs against
     * whatever database the environment is pointed at - which passed locally only because that
     * database happened to be built already, and failed in CI with
     * "Table 'ss_ci.LoginSession' doesn't exist". Driving the CMS needs the full schema (the
     * authentication handler reads session-manager's tables), and a test must never be able to
     * touch a real database.
     */
    protected $usesDatabase = true;

    private function cmsMenuHtml(): string
    {
        $this->logInWithPermission('ADMIN');
        $response = $this->get('admin/pages');

        $this->assertSame(200, $response->getStatusCode(), 'The CMS must load at all');

        return (string) $response->getBody();
    }

    public function testCmsLoadsAndShowsReportsByDefault()
    {
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', null);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertStringContainsString(
            'admin/reports',
            $this->cmsMenuHtml(),
            'Reports must be reachable from the CMS menu when hiding was not opted in'
        );
    }

    public function testReportsDisappearsFromTheMenuWhenOptedIn()
    {
        Config::modify()->set(LeftAndMain::class, 'hide_rarely_used_menu_sections', true);
        LeftAndMainExtension::applyMenuVisibilityConfig();

        $this->assertStringNotContainsString(
            'admin/reports',
            $this->cmsMenuHtml(),
            'Opting in must remove the Reports menu item'
        );
    }

    public function testTheModulesAdminCssAndJsAreRequiredIntoTheCms()
    {
        // admin.yml registers both through LeftAndMain.extra_requirements_*. If the exposed path
        // ever stops resolving, the CMS still loads and the tweaks just silently do nothing.
        $html = $this->cmsMenuHtml();

        $this->assertStringContainsString('admintweaks.css', $html, 'The module CSS must be required into the CMS');
        $this->assertStringContainsString('admintweaks.js', $html, 'The module JS must be required into the CMS');
    }
}
