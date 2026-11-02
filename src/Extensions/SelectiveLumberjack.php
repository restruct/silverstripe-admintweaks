<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Controllers\CMSPagesController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Lumberjack\Forms\GridFieldConfig_Lumberjack;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton;
use SilverStripe\Lumberjack\Model\Lumberjack;

if (!class_exists(Lumberjack::class)) {
    return;
}

class SelectiveLumberjack extends Lumberjack
{

    /**
     * Instead, use config/core functionality to hide pages from sitetree (function left here as fallback)
     * HolderPage:
     *   hide_from_cms_tree:
     *     - 'HLCL\CMS\Pages\Specialist'
     *
     * @return bool
     */
    protected function shouldFilter()
    {
        $controller = Controller::curr();

        # The controller serving admin/pages: CMSPagesController up to Silverstripe 5, CMSMain itself
        # on Silverstripe 6, which removed CMSPagesController (admintweaks#62). Without this the
        # comparison was always false on SS6 and nothing was ever filtered. An exact class match, as
        # before, so the page edit controller (a CMSMain subclass) stays unfiltered on every major.
//        $pagesControllerClass = class_exists(CMSPagesController::class) ? CMSPagesController::class : CMSMain::class;

        # Any CMSMain (admintweaks#105): once a page is open in the editor the tree is loaded again
        # from admin/pages/edit/treeview and getsubtree, served by CMSPageEditController. On SS6
        # that tree listed the hidden children, because Hierarchy::getChildrenForTree() reads
        # hide_from_cms_tree off the node the tree starts from, not off each holder, so this
        # exclusion is the only filter left there. The action list below already keeps the edit
        # form itself and the listview unfiltered, which the exact match was meant to protect.
        # instanceof is also false for null: on SS6 CLI (sake db:build, tasks, jobs) there is no
        # current controller, and get_class(null) threw a TypeError.
//        return get_class($controller) === CMSPagesController::class
//        return get_class($controller) === $pagesControllerClass
        return $controller instanceof CMSMain
            // DON'T filter listview, after all, that's what its for (to show large sets of pages)
            // Original list: 'index', 'show', 'treeview', 'listview', 'getsubtree'
            && in_array($controller->getAction(), [ "treeview", "getsubtree" ]);
    }

    /**
     * Update getExcludedSiteTreeClassNames to also take hide_from_cms_tree (core functionality) into account
     *
     * @return array
     */
    public function getExcludedSiteTreeClassNames()
    {
        $lumberJacked = parent::getExcludedSiteTreeClassNames();
        $hidden = Config::inst()->get($this->owner->className, 'hide_from_cms_tree');
        if(!count($hidden)) {
            return $lumberJacked;
        }
//            var_dump($lumberJacked);
        //array (
        //  'HLCL\\CMS\\Pages\\CatalogItem' => 'HLCL\\CMS\\Pages\\CatalogItem',
        //)
        foreach ($hidden as $classPath){
            $lumberJacked[$classPath] = $classPath;
//                $class = str_replace("\\\\", "\\", $classPath);
//                $lumberJacked[$class] = $class;
        }
//            var_dump($lumberJacked);
        //array (
        //  'HLCL\\CMS\\Pages\\CatalogItem' => 'HLCL\\CMS\\Pages\\CatalogItem',
        //)
//            die();

        return $lumberJacked;
    }

    /**
     * This returns the gridfield config for the lumberjack gridfield.
     *
     * @return GridFieldConfig_Lumberjack
     */
    protected function getLumberjackGridFieldConfig()
    {
        if (method_exists($this->owner, 'getLumberjackGridFieldConfig')) {
            return $this->owner->getLumberjackGridFieldConfig();
        }

        return GridFieldConfig_Lumberjack::create()
            ->removeComponentsByType(GridFieldSiteTreeAddNewButton::class)
            // Version of SiteTreeAddNewButton which takes
            ->addComponent(new \Restruct\Silverstripe\AdminTweaks\Forms\GridFieldSiteTreeAddNewButton('buttons-before-left'))
            ->removeComponentsByType(GridFieldToolbarHeader::class)
            ->removeComponentsByType(GridFieldPageCount::class)
            ->removeComponentsByType(GridFieldSortableHeader::class);
    }

}
