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

    public function testDoesNotFilterOutsideThePagesSection()
    {
        # Parity with the 3.x behaviour: the page edit controller (a CMSMain subclass on every
        # major) was never filtered, and a non-CMS controller must not be either.
        $this->assertFalse($this->shouldFilterOn(CMSPageEditController::class, 'treeview'));
        $this->assertFalse($this->shouldFilterOn(Controller::class, 'treeview'));
    }
}
