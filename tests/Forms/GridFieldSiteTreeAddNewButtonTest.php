<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Forms;

use Restruct\Silverstripe\AdminTweaks\Forms\GridFieldSiteTreeAddNewButton;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton as LumberjackGridFieldSiteTreeAddNewButton;

/**
 * GridFieldSiteTreeAddNewButton adds the page types a parent hides from the CMS tree
 * (`hide_from_cms_tree`) to the Lumberjack "Add new" dropdown (admintweaks#61).
 *
 * Uses core page types (RedirectorPage, VirtualPage) rather than test-only DataObject stubs, so the
 * module ships no fixtures into a consumer's test manifest. Lumberjack is optional for this module:
 * without it the button class does not exist and the tests skip.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata.
 */
class GridFieldSiteTreeAddNewButtonTest extends SapphireTest
{
    # A Member is written by logInWithPermission(), and canAddChildren() needs one.
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(LumberjackGridFieldSiteTreeAddNewButton::class)) {
            $this->markTestSkipped('silverstripe/lumberjack is not installed');
        }
        $this->logInWithPermission('ADMIN');
    }

    public function testHiddenFromCmsTreeClassIsOfferedAsChild()
    {
        # The #61 regression: on Silverstripe 6 this call fatalled on SiteTree::page_type_classes().
        Config::modify()->set(SiteTree::class, 'hide_from_cms_tree', [RedirectorPage::class]);
        $parent = SiteTree::create();

        $children = (new GridFieldSiteTreeAddNewButton())->getAllowedChildren($parent);

        $this->assertArrayHasKey(RedirectorPage::class, $children);
        $this->assertSame(RedirectorPage::singleton()->i18n_singular_name(), $children[RedirectorPage::class]);
        $this->assertArrayNotHasKey(VirtualPage::class, $children, 'Only the hidden classes are added');
    }

    public function testPageTypeHiddenThroughHidePagetypesIsNotOffered()
    {
        # The "non-hidden page types" filter: a class removed from the CMS altogether through
        # SiteTree.hide_pagetypes must not come back through hide_from_cms_tree.
        Config::modify()->set(SiteTree::class, 'hide_from_cms_tree', [RedirectorPage::class]);
        Config::modify()->set(SiteTree::class, 'hide_pagetypes', [RedirectorPage::class]);
        $parent = SiteTree::create();

        $children = (new GridFieldSiteTreeAddNewButton())->getAllowedChildren($parent);

        $this->assertArrayNotHasKey(RedirectorPage::class, $children);
    }

    public function testParentWithoutHiddenClassesReturnsLumberjackDefault()
    {
        # No hide_from_cms_tree configured: the result is exactly Lumberjack's own list, and no
        # "foreach() argument must be of type array" warning (failOnWarning is on in CI).
        Config::modify()->remove(SiteTree::class, 'hide_from_cms_tree');
        $parent = SiteTree::create();

        $this->assertSame(
            (new LumberjackGridFieldSiteTreeAddNewButton())->getAllowedChildren($parent),
            (new GridFieldSiteTreeAddNewButton())->getAllowedChildren($parent)
        );
    }

    public function testNoParentReturnsLumberjackDefault()
    {
        $this->assertSame([], (new GridFieldSiteTreeAddNewButton())->getAllowedChildren(null));
    }
}
