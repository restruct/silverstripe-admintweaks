<?php

namespace Restruct\AtBrowser;

use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - a child of AtBHolder that Lumberjack itself hides from the site tree
 * (show_in_sitetree: false). See AtBHolder.
 */
class AtBLumberjackedPage extends SiteTree
{
    private static $table_name = 'AtBLumberjackedPage';

    private static $singular_name = 'Browser Lumberjacked Page';

    private static $can_be_root = false;

    private static $allowed_children = 'none';

    private static $show_in_sitetree = false;
}
