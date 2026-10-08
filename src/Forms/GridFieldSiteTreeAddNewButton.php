<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton as LumberjackGridFieldSiteTreeAddNewButton;
if (!class_exists(LumberjackGridFieldSiteTreeAddNewButton::class)) {
    return;
}

class GridFieldSiteTreeAddNewButton
    extends LumberjackGridFieldSiteTreeAddNewButton
{
    /**
     * Overriding classnames and titles allowed for a given parent object
     *
     * @param SiteTree $parent
     * @return boolean
     */
    public function getAllowedChildren(?SiteTree $parent = null)
    {
        $children = parent::getAllowedChildren($parent);
        if (!$parent || !$parent->canAddChildren()) {
            return $children;
        }

//        $nonHiddenPageTypes = SiteTree::page_type_classes();
        # SiteTree::page_type_classes() was removed in Silverstripe 6 (admintweaks#61). Its documented
        # replacement: all SiteTree subclasses, minus the base class and anything in
        # SiteTree.hide_pagetypes. Through invokeWithExtensions(), as CMSMain::getAllowedSubClasses()
        # does, so project extensions hooking updateAllowedSubClasses() drop their page types here
        # too (Lumberjack's own button calls the method directly and misses them).
        $nonHiddenPageTypes = ClassInfo::getValidSubClasses(SiteTree::class);
        SiteTree::singleton()->invokeWithExtensions('updateAllowedSubClasses', $nonHiddenPageTypes);
//        $extraHiddenClasses = Config::inst()->get($parent->className, 'hide_from_cms_tree');
        # `?? []`: a parent without hide_from_cms_tree returns null, and foreach over null warns.
        $extraHiddenClasses = Config::inst()->get($parent->className, 'hide_from_cms_tree') ?? [];
        foreach ($extraHiddenClasses as $class){
            $instance = Injector::inst()->get($class);
            if ($instance->canCreate(null, array('Parent' => $parent)) && in_array($class, $nonHiddenPageTypes)) {
                $children[$class] = $instance->i18n_singular_name();
            }
        }

        return $children;
    }


}
