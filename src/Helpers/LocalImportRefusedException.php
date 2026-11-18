<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;

/**
 * Thrown by GeneralHelpers::import_file_asset() when it is handed a local path or a `file:` URL
 * it may not read: no $localBaseDir was passed, the base directory does not exist, or the file
 * resolves (symlinks and `..` included) to somewhere outside it (admintweaks#151).
 *
 * Extends the plain Exception the helper already throws, so existing catch blocks keep catching
 * it.
 */
class LocalImportRefusedException extends Exception
{
}
