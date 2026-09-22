<?php

namespace Restruct\Silverstripe\AdminTweaks\Traits {

    use SilverStripe\Security\Permission;

    trait EnforceCMSPermission
    {

        /*
          * Base permissions:
          * ADMIN = all
          * CMS_ACCESS_LeftAndMain = base admin/cms access
          * CMS_ACCESS_CMSMain = pages
          * CMS_ACCESS_SecurityAdmin = users
          * CMS_ACCESS_MediaAdmin = media/files
          * CMS_ACCESS_ReportAdmin = reports
          */
        public function canView($member = null)
        {
            return Permission::check('CMS_ACCESS_CMSMain');
        }

        public function canEdit($member = null)
        {
            return $this->canView();
        }

        public function canDelete($member = null)
        {
            return $this->canView();
        }

        /**
         * @param null  $member
         * @param array $context
         *
         * @return bool|int
         */
        public function canCreate($member = null, $context = [])
        {
            return $this->canView();
        }

    }

}