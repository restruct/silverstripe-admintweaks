<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;

/**
 * Thrown by OutboundUrlGuard when a URL may not be fetched: a scheme other than http(s), a local
 * name, or a host that resolves to a non-public address (admintweaks#139).
 *
 * Extends the plain Exception the HTTP helpers already throw, so existing catch blocks keep
 * catching it.
 */
class OutboundUrlRejectedException extends Exception
{
}
