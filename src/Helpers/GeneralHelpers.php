<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\Tab;

class GeneralHelpers
{
    /**
     * Insert a tab at a certain position (if it doesnt exist yet)
     *
     * @param $fields FieldList
     * @param $tabName string Tab.Subtab.Subsubtab notation
     * @param $tabTitle string
     * @param $insertAfter string
     * @return void
     */
    public static function add_tab_if_not_exists($fields, $tabName, $tabTitle=null, $insertAfter=null)
    {
        $tabPath = str_starts_with((string) $tabName, 'Root.') ? $tabName : 'Root.' . $tabName;
        if($fields->findTab($tabPath)) {
            return;
        }

        if(!$insertAfter){
            $fields->findOrMakeTab($tabPath, $tabTitle);
        } else {
            $fields->insertAfter('Main',
                Tab::create($tabName, $tabTitle),
                true
            );
        }
    }

    /**
     * Get translations for options array, eg for translating dropdown options
     * Falls back to raw option value in case no translation exists
     *
     * @param array $options eg [ 'left', 'right', 'below' ]
     * @param string $prefix eg ClassName . '.FieldName_'
     * @return array
     */
    public static function get_options_translations(array $options, $prefix='')
    {
        $translations = array_combine($options, $options);
        foreach ($options as $option){
            $translations[$option] = _t($prefix . $option, $option);
        }

        return $translations;
    }

    // safely get nested properties (returns null if not exists at some point)
    public static function safelyGetProperty($object, $prop_arr)
    {
        // convert to array if string
        if ( is_string($prop_arr) ) {
            $prop_arr = explode('->', $prop_arr);
        }

        // get property to test for
        $prop = array_shift($prop_arr);
        // return null if not property exists
        if ( !is_object($object) || !property_exists($object, $prop) ) {
            return null;
        }

        if ( count($prop_arr) == 0 ) {
            return $object->{$prop};
        } // else, if final property, return value

        // else, call self recursively on existing property
        return self::safelyGetProperty($object->{$prop}, $prop_arr);
    }

    // Get file assets file, also when not published yet
    public static function getFileAssetsPath($file)
    {
        $meta = $file->getMetaData();
        if ($meta == null) {
            return null;
        }
        if (!isset($meta['path'])) {
            return null;
        }
        if (!$path = $meta['path']) {
            return null;
        }
        if ($rootpath = Environment::getEnv('SS_PROTECTED_ASSETS_PATH')) {
            return $rootpath . DIRECTORY_SEPARATOR . $path;
        }
        return ASSETS_PATH . '/.protected' . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * (Down)load a file from path or url and store it as File/Image asset object
     *
     * @param string $filePathOrUrl URL to internet file or path to local file (direct or with 'file:' prefix)
     * @param string $assetDir folder/directory in which to create asset
     * @param string|null $fileName name to assign to File asset
     * @return File|Image|null
     * @throws Exception
     */
    public static function import_file_asset(string $filePathOrUrl, string $assetPath=null, bool $publish=true)
    {
        return self::download_and_save_asset($filePathOrUrl, $assetPath, $publish);
    }

    // Download/load a file into assets (set to private and replaced with add_file_to_assets in order to phase out $write argument)
    private static function download_and_save_asset($filePathOrUrl, $assetPath=null, $publish=true)
    {
        // fallback to just filename of original file
        $fileName = basename((string) $assetPath ?: (string) $filePathOrUrl);
        $fileExt = File::get_file_extension($fileName);
        $dirPath = ltrim(dirname(trim($assetPath ?? '', '/')), '.'); // copied from Folder::find_or_make
        $assetPath = implode(DIRECTORY_SEPARATOR, array_filter([$dirPath, $fileName]));

        // Check if allowed file type
        if(!in_array($fileExt, File::getAllowedExtensions())){
            throw new Exception(sprintf('FILE EXTENSION NOT ALLOWED IN ASSETS (%s)', $fileExt));
        }

        $File = File::find($assetPath);
        if(!$File) {
            $File = File::get_app_category($fileExt) == 'image' ? Image::create() : File::create();
        }

        // Create pointer to (temp) local file
        if(str_starts_with((string) $filePathOrUrl, 'file:')) {
            $localFilePath = '/' . str_replace(['file:///', 'file://', 'file:/', 'file:'], '', $filePathOrUrl);
        } elseif (is_file($filePathOrUrl)) {
            $localFilePath = $filePathOrUrl;
        } else {
            $client = new Client();
            $localFilePath = tempnam(sys_get_temp_dir(), 'asset');
            $client->request('GET', $filePathOrUrl, ['sink' => fopen($localFilePath, 'w')]);
        }

        // Create & save to File https://api.silverstripe.org/4/SilverStripe/Assets/File.html#method_setFromLocalFile
        // setFromLocalFile(string $path, string $filename = null, string $hash = null, string $variant = null, array $config = []) Assign a local file to the backend.
        // setFromStream(resource $stream, string $filename, string $hash = null, string $variant = null, array $config = []) Assign a stream to the backend
        // setFromString(string $data, string $filename, string $hash = null, string $variant = null, array $config = []) Assign a set of data to the backend
        $File->setFromLocalFile($localFilePath, $assetPath, null, null, [ 'conflict' => AssetStore::CONFLICT_OVERWRITE, ]);

        // ->generateThumbnails for asset manager (for some reason only works after first ->write())
        $File->write();
        AssetAdmin::singleton()->generateThumbnails($File);

        if($publish) {
            $File->publishRecursive();
        }

        return $File;
    }

    /**
     * Perform a HTTP request via Guzzle and return the response
     *
     * @param $reqUrl
     * @param string $reqMethod
     * @return array [ 'body'=>..., 'statuscode'=>..., 'requesturl'=>... 'effectiveurl'=>..., ]
     * @throws Exception
     */
    public static function perform_http_request($reqUrl, $reqMethod = 'GET', array $options = [])
    {
        // Note: Guzzle is a HTTP client, either require guzzle directly or via a (Guzzle)HTTPlug (adapter)
        // HTTPlug allows you to write reusable libraries and applications that need an HTTP client without
        // binding to a specific implementation (https://docs.php-http.org/en/latest/httplug/introduction.html)
        // Using an adapter makes Guzzle conform to PSR-18 https://www.php-fig.org/psr/psr-18/ (Guzzle7 does out of the box)
        try {
            $client = new Client();
            // Making Guzzle requests: https://docs.guzzlephp.org/en/stable/
//            $options['on_stats'] = function (TransferStats $stats) use (&$effectiveUrl) {
//                $effectiveUrl = $stats->getEffectiveUri();
//            };
            $response = $client->request($reqMethod, $reqUrl, $options);

        } catch ( GuzzleException $guzzleException ) {
            throw new Exception(sprintf('GUZZLE EXCEPTION [%d]: %s', $guzzleException->getCode(), $guzzleException->getMessage()), $guzzleException->getCode(), $guzzleException);
        }

        return [
            'body' => $response->getBody()->getContents(),
            'statuscode' => $response->getStatusCode(),
            'requesturl' => $reqUrl,
//            'effectiveurl' => $effectiveUrl,
            'effectiveurl' => $reqUrl, // maintain array key for compatibility (but just fill with requested URL instead)
        ];
    }
}
