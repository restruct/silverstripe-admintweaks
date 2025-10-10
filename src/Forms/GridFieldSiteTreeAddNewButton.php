<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton as LumberjackGridFieldSiteTreeAddNewButton;
use SilverStripe\ORM\HiddenClass;

if (!class_exists(LumberjackGridFieldSiteTreeAddNewButton::class)) {
    return;
}

class GridFieldSiteTreeAddNewButton
    extends LumberjackGridFieldSiteTreeAddNewButton
{
    /**
     * Overriding classnames and titles allowed for a given parent object
     *
     * @return boolean
     */
    public function getAllowedChildren(SiteTree $parent = null)
    {
        $children = parent::getAllowedChildren($parent);
        if (!$parent instanceof SiteTree || !$parent->canAddChildren()) {
            return $children;
        }

        // Get non-hidden page types (SS6 compatible - page_type_classes() was removed)
        $nonHiddenPageTypes = $this->getNonHiddenPageTypes();
        $extraHiddenClasses = Config::inst()->get($parent->className, 'hide_from_cms_tree');
        foreach ($extraHiddenClasses as $class){
            $instance = Injector::inst()->get($class);
            if ($instance->canCreate(null, ['Parent' => $parent]) && in_array($class, $nonHiddenPageTypes)) {
                $children[$class] = $instance->i18n_singular_name();
            }
        }

        return $children;
    }

    /**
     * Get all non-hidden SiteTree subclasses
     * Replacement for removed SiteTree::page_type_classes() in SS6
     *
     * @return array
     */
    protected function getNonHiddenPageTypes()
    {
        $classes = ClassInfo::subclassesFor(SiteTree::class);
        $result = [];

        foreach ($classes as $class) {
            // Skip classes marked with HiddenClass interface
            if (!is_a($class, HiddenClass::class, true)) {
                $result[] = $class;
            }
        }

        return $result;
    }


}
