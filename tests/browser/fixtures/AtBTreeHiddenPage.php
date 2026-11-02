<?php

namespace Restruct\AtBrowser;

use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - a child of AtBHolder that is hidden from the site tree only by the
 * holder's hide_from_cms_tree (it keeps the default show_in_sitetree). See AtBHolder.
 */
class AtBTreeHiddenPage extends SiteTree
{
    private static $table_name = 'AtBTreeHiddenPage';

    private static $singular_name = 'Browser Tree-hidden Page';

    private static $can_be_root = false;

    private static $allowed_children = 'none';
}
