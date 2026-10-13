<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests;

use Restruct\Silverstripe\AdminTweaks\Extensions\SystemSwitcherExtension;
use Restruct\Silverstripe\AdminTweaks\SystemSwitcher;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * CMS system switcher (admintweaks#71): config shape and merging, environment suffix, the admin
 * title, and the menu header template with and without systems configured.
 */
class SystemSwitcherTest extends SapphireTest
{
    # The header templates read $SiteConfig
    protected $usesDatabase = true;

    private const SYSTEMS = [
        'site' => ['display' => 'Example SITE', 'url' => 'https://www.example.org/admin'],
        'docs' => ['display' => 'Example DOCS', 'url' => 'https://docs.example.org/admin'],
        'lms' => ['display' => 'Example LMS', 'url' => 'https://learn.example.org/admin'],
    ];

    private ?string $originalEnvironment = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnvironment = Injector::inst()->get(Kernel::class)->getEnvironment();
        # Start from the module defaults whatever the host project configures
        Config::modify()->set(SystemSwitcher::class, 'systems', []);
        Config::modify()->set(SystemSwitcher::class, 'current', null);
        Config::modify()->set(LeftAndMain::class, 'application_name', 'Example App');
        $this->setEnvironment('test');
    }

    protected function tearDown(): void
    {
        Injector::inst()->get(Kernel::class)->setEnvironment($this->originalEnvironment);
        parent::tearDown();
    }

    private function setEnvironment(string $type): void
    {
        Injector::inst()->get(Kernel::class)->setEnvironment($type);
    }

    private function configure(?string $current = 'site', array $systems = self::SYSTEMS): void
    {
        Config::modify()->set(SystemSwitcher::class, 'systems', $systems);
        Config::modify()->set(SystemSwitcher::class, 'current', $current);
    }

    private function switcher(): SystemSwitcher
    {
        return SystemSwitcher::create();
    }

    public function testOffWithoutSystems(): void
    {
        $this->assertFalse($this->switcher()->isEnabled());
        $this->assertNull(LeftAndMain::singleton()->AdminSystemSwitcher());

        # The title is left alone too
        $admin = SecurityAdmin::create();
        $admin->extend('onBeforeInit');
        $this->assertSame('Example App', $admin->getApplicationName());
    }

    public function testEnvironmentSuffixPerEnvironmentType(): void
    {
        $this->configure();

        $this->setEnvironment('test');
        $this->assertSame(' (TEST)', $this->switcher()->getEnvironmentSuffix());
        $this->assertSame('Example SITE (TEST)', $this->switcher()->getCurrentLabel());

        $this->setEnvironment('dev');
        $this->assertSame('Example SITE (DEV)', $this->switcher()->getCurrentLabel());

        $this->setEnvironment('live');
        $this->assertSame('', $this->switcher()->getEnvironmentSuffix());
        $this->assertSame('Example SITE', $this->switcher()->getCurrentLabel());
    }

    public function testSuffixStringsAreConfigurable(): void
    {
        $this->configure();
        Config::modify()->merge(SystemSwitcher::class, 'environment_suffixes', ['test' => ' [acceptance]']);

        $this->assertSame('Example SITE [acceptance]', $this->switcher()->getCurrentLabel());
    }

    public function testCurrentNameDefaultsToApplicationName(): void
    {
        # 'site' configured without a display
        $systems = self::SYSTEMS;
        unset($systems['site']['display']);
        $this->configure('site', $systems);

        $this->assertSame('Example App', $this->switcher()->getCurrentName());
        $this->assertSame('Example App (TEST)', $this->switcher()->getCurrentLabel());

        # And with no current at all
        $this->configure(null);
        $this->assertSame('Example App (TEST)', $this->switcher()->getCurrentLabel());
    }

    public function testApplicationNameAlreadyEndingInTheSuffixIsNotDoubled(): void
    {
        $this->configure(null);
        Config::modify()->set(LeftAndMain::class, 'application_name', 'Example App (TEST)');

        $this->assertSame('Example App', $this->switcher()->getCurrentName());
        $this->assertSame('Example App (TEST)', $this->switcher()->getCurrentLabel());
    }

    public function testAdminTitleCarriesTheSuffixOnce(): void
    {
        $this->configure();
        $admin = SecurityAdmin::create();
        $section = $admin->SectionTitle();

        $admin->extend('onBeforeInit');
        $this->assertSame('Example SITE (TEST)', $admin->getApplicationName());
        $this->assertSame("Example SITE (TEST) - $section", $admin->Title());

        # A second init in the same request (or another controller of the same class) does not double it
        $admin->extend('onBeforeInit');
        SecurityAdmin::create()->extend('onBeforeInit');
        $this->assertSame("Example SITE (TEST) - $section", $admin->Title());

        # Only the handling controller's class is adjusted: LeftAndMain's own config (read by eg
        # login-branding's SiteConfig title override) keeps the configured name
        $this->assertSame('Example App', LeftAndMain::config()->get('application_name'));
    }

    public function testEnvironmentFragmentOverridingOnlyUrlKeepsDisplay(): void
    {
        $this->configure();
        # What an `Only: environment: test` YAML fragment does: YAML fragments and Config::merge() both
        # merge through silverstripe/config's Priority::mergeArray(), recursive for string keys
        Config::modify()->merge(SystemSwitcher::class, 'systems', [
            'docs' => ['url' => 'https://docs-test.example.org/admin'],
        ]);

        $docs = $this->switcher()->getOtherSystems()->find('Key', 'docs');
        $this->assertSame('Example DOCS', $docs->Display);
        $this->assertSame('https://docs-test.example.org/admin', $docs->URL);
    }

    public function testOtherSystemsLeaveOutCurrentAndInvalidEntries(): void
    {
        $this->configure('site', self::SYSTEMS + [
            'js' => ['display' => 'Script', 'url' => 'javascript:alert(1)'],
            'relative' => ['display' => 'Relative', 'url' => '/admin'],
            'ftp' => ['display' => 'FTP', 'url' => 'ftp://files.example.org/'],
            'nodisplay' => ['url' => 'https://nodisplay.example.org/admin'],
            'nourl' => ['display' => 'No URL'],
            'notarray' => 'https://string.example.org/admin',
        ]);

        $this->assertSame(['docs', 'lms'], $this->switcher()->getOtherSystems()->column('Key'));
    }

    public function testUrlValidation(): void
    {
        $this->assertTrue(SystemSwitcher::isValidUrl('https://docs.example.org/admin'));
        $this->assertTrue(SystemSwitcher::isValidUrl('http://localhost:8080/admin'));
        $this->assertFalse(SystemSwitcher::isValidUrl('javascript:alert(1)'));
        $this->assertFalse(SystemSwitcher::isValidUrl('data:text/html,hi'));
        $this->assertFalse(SystemSwitcher::isValidUrl('//docs.example.org/admin'));
        $this->assertFalse(SystemSwitcher::isValidUrl('/admin'));
        $this->assertFalse(SystemSwitcher::isValidUrl(''));
    }

    public function testInitials(): void
    {
        $this->configure();
        $this->assertSame('ES', $this->switcher()->getCurrentInitials());

        $this->configure('docs', ['docs' => ['display' => 'docs', 'url' => 'https://docs.example.org/admin']]);
        $this->assertSame('DO', $this->switcher()->getCurrentInitials());

        $this->configure('docs', ['docs' => ['display' => 'Docs', 'initials' => 'Dx', 'url' => 'https://docs.example.org/admin']]);
        $this->assertSame('Dx', $this->switcher()->getCurrentInitials());
    }

    /**
     * Renders the menu header include the way LeftAndMain_Menu.ss does, with the admin themes.
     */
    private function renderMenuLogo(LeftAndMain $admin): string
    {
        SSViewer::set_themes(LeftAndMain::config()->get('admin_themes'));
        return (string) $admin->renderWith(['type' => 'Includes', 'SilverStripe\\Admin\\LeftAndMain_MenuLogo']);
    }

    public function testMenuLogoOverrideIsTheOneResolved(): void
    {
        # Both this module and silverstripe/admin ship SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss;
        # the switcher only exists if this module's copy wins the template lookup
        $path = ThemeResourceLoader::inst()->findTemplate(
            ['type' => 'Includes', 'SilverStripe\\Admin\\LeftAndMain_MenuLogo'],
            LeftAndMain::config()->get('admin_themes')
        );
        $module = ModuleLoader::getModule('restruct/silverstripe-admintweaks');
        $this->assertSame(
            realpath($module->getResource('templates/SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss')->getPath()),
            realpath($path)
        );
    }

    public function testHeaderUnchangedWithoutSystems(): void
    {
        $admin = SecurityAdmin::create();
        $corePath = ModuleLoader::getModule('silverstripe/admin')
            ->getResource('templates/SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss')->getPath();

        $core = (string) $admin->renderWith($corePath);
        $this->assertStringContainsString('cms-sitename__title', $core, 'control: core header rendered');
        $this->assertSame(trim($core), trim($this->renderMenuLogo($admin)));
    }

    public function testHeaderWithSystemsRendersSwitcherEscaped(): void
    {
        $this->configure('site', self::SYSTEMS + [
            'tricky' => ['display' => '<img src=x onerror=alert(1)> "Tricky"', 'url' => 'https://tricky.example.org/?a=1&b="2"'],
        ]);
        Config::modify()->merge(SystemSwitcher::class, 'systems', [
            'site' => ['display' => 'Example <b>SITE</b>'],
        ]);

        $html = $this->renderMenuLogo(SecurityAdmin::create());

        $this->assertStringContainsString('at-system-switcher__toggle', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringNotContainsString('cms-sitename__link', $html, 'core header replaced');
        $this->assertStringContainsString('href="https://docs.example.org/admin"', $html);
        $this->assertStringNotContainsString('href="https://www.example.org/admin"', $html, 'current system not listed');
        # Configured strings escaped, in text and in attributes
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt; &quot;Tricky&quot;', $html);
        $this->assertStringContainsString('Example &lt;b&gt;SITE&lt;/b&gt; (TEST)', $html);
        $this->assertStringContainsString('href="https://tricky.example.org/?a=1&amp;b=&quot;2&quot;"', $html);
    }

    public function testExtensionIsApplied(): void
    {
        $this->assertTrue(LeftAndMain::has_extension(SystemSwitcherExtension::class));
    }
}
