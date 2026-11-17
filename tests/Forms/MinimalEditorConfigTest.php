<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Forms;

use Restruct\Silverstripe\AdminTweaks\Forms\MinimalEditorConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

/**
 * The `minimal` HTML editor config (admintweaks#116): a copy of `cms`, made on the first
 * MinimalEditorConfig::get(), with a one-line toolbar from YAML. Up to Silverstripe 5 the getter
 * is what makes it; the name works once it has been called.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata, static
 * data providers only.
 */
class MinimalEditorConfigTest extends SapphireTest
{
    protected $usesDatabase = false;

    /** The toolbar the issue asked for, as getButtons() returns it (empty lines filtered out). */
    private const DEFAULT_BUTTONS = [
        1 => ['bold', 'italic', '|', 'bullist', 'numlist', '|', 'sslink', 'unlink', '|', 'removeformat'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        # The registry is static and outlives a test: drop `minimal` so every test sees it
        # created afresh (and from the config as this test set it).
        HTMLEditorConfig::set_config(MinimalEditorConfig::NAME, null);
    }

    protected function tearDown(): void
    {
        # Leave no `minimal` built from this test's config modifications behind.
        HTMLEditorConfig::set_config(MinimalEditorConfig::NAME, null);
        parent::tearDown();
    }

    public function testIsCreatedOnRequestAsSeparateTinyMceConfig(): void
    {
        $minimal = MinimalEditorConfig::get();
        $cms = HTMLEditorConfig::get('cms');

        $this->assertInstanceOf(TinyMCEConfig::class, $minimal);
        $this->assertNotSame($cms, $minimal, 'minimal is a copy, not the cms config itself');
        $this->assertSame(MinimalEditorConfig::NAME, $minimal->getOption('editorIdentifier'));
        $this->assertSame('Simple', $minimal->getOption('friendly_name'));
        $this->assertSame('sslink', $minimal->getOption('contextmenu'));
        # One instance, however it is asked for once the getter made it.
        $this->assertSame($minimal, MinimalEditorConfig::get());
        $this->assertSame($minimal, MinimalEditorConfig::get(MinimalEditorConfig::NAME));
        $this->assertSame($minimal, HTMLEditorConfig::get(MinimalEditorConfig::NAME));
        $this->assertSame($minimal, TinyMCEConfig::get(MinimalEditorConfig::NAME));
    }

    public function testHasTheConfiguredButtonsOnOneLine(): void
    {
        $minimal = MinimalEditorConfig::get();

        # getButtons() filters out empty lines, so this also says lines 2 and 3 are empty.
        $this->assertSame(self::DEFAULT_BUTTONS, $minimal->getButtons());
    }

    public function testKeepsTheCmsLinkPlugins(): void
    {
        $plugins = MinimalEditorConfig::get()->getPlugins();

        # Registered on `cms` by admin's and cms's _config.php;
        # a fresh TinyMCEConfig has none of them.
        $this->assertArrayHasKey('sslink', $plugins);
        $this->assertArrayHasKey('sslinkexternal', $plugins);
        $this->assertArrayHasKey('sslinkemail', $plugins);
        if (ModuleLoader::inst()->getManifest()->moduleExists('silverstripe/cms')) {
            $this->assertArrayHasKey('sslinkinternal', $plugins);
            $this->assertArrayHasKey('sslinkanchor', $plugins);
        }
        $this->assertArrayNotHasKey('sslink', TinyMCEConfig::create()->getPlugins(), 'control: a fresh config has no sslink');
    }

    public function testCopiesCmsAsItIsAtFirstRequestNotAtBoot(): void
    {
        $cms = HTMLEditorConfig::get('cms');
        # A plugin added to `cms` after every _config.php has run - as one added by a module whose
        # _config.php runs after this module's would be, from the copy's point of view.
        $cms->enablePlugins(['atbprobe' => null]);
        try {
            $this->assertArrayHasKey('atbprobe', MinimalEditorConfig::get()->getPlugins());
        } finally {
            $cms->disablePlugins('atbprobe');
        }
    }

    public function testLeavesTheCmsConfigAlone(): void
    {
        $cms = HTMLEditorConfig::get('cms');
        $buttonsBefore = $cms->getButtons();
        $nameBefore = $cms->getOption('friendly_name');

        MinimalEditorConfig::get();

        $this->assertSame($buttonsBefore, $cms->getButtons());
        $this->assertSame($nameBefore, $cms->getOption('friendly_name'));
        $this->assertSame('cms', $cms->getOption('editorIdentifier'));
    }

    public function testFieldTakesTheConfigAndItsNameOnceTheGetterRan(): void
    {
        $minimal = MinimalEditorConfig::get();

        $this->assertSame($minimal, HTMLEditorField::create('Intro')->setEditorConfig($minimal)->getEditorConfig());
        $this->assertSame($minimal, HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::NAME)->getEditorConfig());
    }

    public static function provideButtonConfig(): array
    {
        return [
            'space-separated strings' => [
                ['line_1' => 'bold  italic | link', 'line_2' => 'undo redo', 'line_3' => ''],
                [1 => ['bold', 'italic', '|', 'link'], 2 => ['undo', 'redo']],
            ],
            'lists' => [
                ['line_1' => ['bold', 'italic'], 'line_2' => [], 'line_3' => ['code']],
                [1 => ['bold', 'italic'], 3 => ['code']],
            ],
            'a line left out is emptied too' => [
                ['line_1' => 'bold'],
                [1 => ['bold']],
            ],
        ];
    }

    public function testButtonsAreConfigurable(): void
    {
        # One loop instead of a data provider: PHPUnit 9 reads providers from doc comments and 11
        # from attributes, and the suite runs on both.
        foreach (static::provideButtonConfig() as $case => [$buttonConfig, $expected]) {
            Config::modify()->set(MinimalEditorConfig::class, 'minimal_buttons', $buttonConfig);
            HTMLEditorConfig::set_config(MinimalEditorConfig::NAME, null);

            $this->assertSame($expected, MinimalEditorConfig::get()->getButtons(), $case);
        }
    }

    public function testAProjectsOwnMinimalConfigWins(): void
    {
        $own = TinyMCEConfig::create();
        HTMLEditorConfig::set_config(MinimalEditorConfig::NAME, $own);

        $this->assertSame($own, MinimalEditorConfig::get());
    }

    public function testAnotherIdentifierIsPassedToHtmlEditorConfig(): void
    {
        $this->assertSame(HTMLEditorConfig::get('cms'), MinimalEditorConfig::get('cms'));

        # An unknown name is created blank, as by HTMLEditorConfig::get(), not as a minimal copy.
        try {
            $this->assertArrayNotHasKey('sslink', MinimalEditorConfig::get('atb-other')->getPlugins());
        } finally {
            HTMLEditorConfig::set_config('atb-other', null);
        }
    }
}
