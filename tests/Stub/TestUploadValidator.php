<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Dev\TestOnly;

/**
 * Test-only upload validator: accepts an existing local file as an upload. The real one requires
 * is_uploaded_file(), which a file written by a test can never satisfy. Swapped in through the
 * Injector by ModelAdminExtensionTest only.
 */
class TestUploadValidator extends Upload_Validator implements TestOnly
{
    public function isValidUpload()
    {
        return !empty($this->tmpFile['tmp_name']) && is_file($this->tmpFile['tmp_name']);
    }
}
