<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use ReflectionMethod;
use ReflectionProperty;
use Restruct\Silverstripe\AdminTweaks\Extensions\SelectiveLumberjack;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Controllers\CMSPagesController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Lumberjack\Model\Lumberjack;

/**
 * SelectiveLumberjack filters the CMS site TREE (treeview, getsubtree) of the main Pages section,
 * and nothing else - the listview is what Lumberjack children are meant to be found in.
 * The Pages section includes the page edit screen, which loads the same tree (admintweaks#105).
 *
 * admintweaks#62: on Silverstripe 6 there is no CMSPagesController, so the filter never applied.
 * CMSMain itself serves admin/pages there.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata, static
 * data providers only.
 */
class SelectiveLumberjackTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Lumberjack::class)) {
            $this->markTestSkipped('silverstripe/lumberjack is not installed');
        }
    }

    /**
     * The controller that serves admin/pages: CMSPagesController on SS4/5, CMSMain on SS6.
     */
    private static function pagesControllerClass(): string
    {
        return class_exists(CMSPagesController::class) ? CMSPagesController::class : CMSMain::class;
    }

    private function shouldFilterOn(string $controllerClass, string $action): bool
    {
        $controller = $controllerClass::create();
        # Controller::$action is only set while a request is handled; set it as handleAction() would.
        $prop = new ReflectionProperty(Controller::class, 'action');
        $prop->setAccessible(true);
        $prop->setValue($controller, $action);

        # pushCurrent() reads the session off the request.
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);

        $controller->pushCurrent();
        try {
            $method = new ReflectionMethod(SelectiveLumberjack::class, 'shouldFilter');
            $method->setAccessible(true);
            return $method->invoke(new SelectiveLumberjack());
        } finally {
            $controller->popCurrent();
        }
    }

    public function testFiltersTheTreeInThePagesSection()
    {
        $class = self::pagesControllerClass();
        $this->assertTrue($this->shouldFilterOn($class, 'treeview'), "$class treeview");
        $this->assertTrue($this->shouldFilterOn($class, 'getsubtree'), "$class getsubtree");
    }

    public function testDoesNotFilterTheListview()
    {
        $this->assertFalse($this->shouldFilterOn(self::pagesControllerClass(), 'listview'));
    }

    public function testFiltersTheTreeOnThePageEditScreen()
    {
        # admintweaks#105: once a page is open in the editor the CMS loads the site tree again,
        # from admin/pages/edit/treeview and getsubtree, served by CMSPageEditController. On SS6
        # that tree listed the hidden children. Its tree actions must be filtered like the Pages
        # section's.
        $this->assertTrue($this->shouldFilterOn(CMSPageEditController::class, 'treeview'), 'edit treeview');
        $this->assertTrue($this->shouldFilterOn(CMSPageEditController::class, 'getsubtree'), 'edit getsubtree');
    }

    public function testDoesNotFilterTheEditFormItself()
    {
        # Only the tree actions: the edit form (show / EditForm) and the listview stay unfiltered.
        $this->assertFalse($this->shouldFilterOn(CMSPageEditController::class, 'show'));
        $this->assertFalse($this->shouldFilterOn(CMSPageEditController::class, 'EditForm'));
        $this->assertFalse($this->shouldFilterOn(CMSPageEditController::class, 'listview'));
    }

    public function testDoesNotFilterWithoutACurrentController()
    {
        # CLI on Silverstripe 6 (sake db:build, tasks, queued jobs) runs without a current
        # controller. Reading a SelectiveLumberjack holder's children there (e.g. deleting or
        # unpublishing it in requireDefaultRecords) went through get_class(null), a TypeError.
        # Found by the #105 browser fixture's re-seed.
        # The test run itself has a controller on the stack: empty it as CLI has it, and put it back.
        $stack = new ReflectionProperty(Controller::class, 'controller_stack');
        $stack->setAccessible(true);
        $saved = $stack->getValue();
        $stack->setValue(null, []);
        try {
            $this->assertNull(Controller::curr(), 'precondition: no current controller');
            $method = new ReflectionMethod(SelectiveLumberjack::class, 'shouldFilter');
            $method->setAccessible(true);
            $this->assertFalse($method->invoke(new SelectiveLumberjack()));
        } finally {
            $stack->setValue(null, $saved);
        }
    }

    public function testDoesNotFilterOutsideThePagesSection()
    {
        # Parity with the 3.x behaviour: the page edit controller (a CMSMain subclass on every
        # major) was never filtered, and a non-CMS controller must not be either.
        # admintweaks#105 changed the first half on purpose: the edit controller's TREE actions are
        # filtered now (testFiltersTheTreeOnThePageEditScreen), its form actions still are not
        # (testDoesNotFilterTheEditFormItself). A non-CMS controller stays unfiltered.
//        $this->assertFalse($this->shouldFilterOn(CMSPageEditController::class, 'treeview'));
        $this->assertFalse($this->shouldFilterOn(Controller::class, 'treeview'));
    }
}
