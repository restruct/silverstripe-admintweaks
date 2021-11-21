<?php

use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\View\ThemeResourceLoader;

class TemplateHelpers
    implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'themeDirResourceURL',
        ];
    }

    /**
     * Template $themeDirResourceURL($themeDir) method
     * Resolves resource specifier (URL) to the given $themeDir
     *
     * @param string $themePath
     * @return string
     */
    public static function themeDirResourceURL($themeDir)
    {
        if (empty($themeDir)) {
            return null;
        }

        // Resolve resource to reference
        $themeDirPath = ThemeResourceLoader::inst()->getPath($themeDir);

        // Resolve resource to url
        return ModuleResourceLoader::resourceURL($themeDirPath);
    }
}