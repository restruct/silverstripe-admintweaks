<?php

use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ViewableData;

class TemplateHelpers
    implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return [
            'themeDirResourceURL',
            'ImagePlaceholder',
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

    public static function ImagePlaceholder($W, $H, $Label='', $AddClass='', $DataUriBase64=false)
    {
        $svgStr = ViewableData::create()
            ->customise([
                'W' => (int) $W,
                'H' => (int) $H,
                'Label' => Convert::raw2xml($Label),
                'AddClass' => Convert::raw2att($AddClass)
            ])
            ->renderWith('Includes\ImagePlaceholder');
        $svgStr = str_replace('<br />', '', $svgStr);
        $svgStr = str_replace("\n", '', $svgStr);

        // just always base64 escape to circumvent issues with svg-in-html-attribute (eg '"'s)
//        return $Base64 ? "data:image/svg+xml;base64,".base64_encode($svgStr) : "data:image/svg+xml;utf8,{$svgStr}";
        return $DataUriBase64 ? "data:image/svg+xml;base64,".base64_encode($svgStr) : DBHTMLVarchar::create()->setValue($svgStr);
    }
}
