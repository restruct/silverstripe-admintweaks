<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\TransferStats;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
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
        $tabPath = strpos($tabName, 'Root.')===0 ? $tabName : "Root.{$tabName}";
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
            $translations[$option] = _t("{$prefix}{$option}", $option);
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

    /**
     * Get the absolute local filesystem path for a File asset.
     *
     * Delegates to FileLocalPathExtension::getLocalPath() which checks both
     * public and protected stores with hash-prefixed and natural paths.
     *
     * For reading file content only (not passing to external tools), prefer $file->getString().
     *
     * @param File|null $file
     * @return string|null Absolute filesystem path, or null if not found
     */
    public static function getFileAssetsPath($file): ?string
    {
        if (!$file || !$file->exists()) {
            return null;
        }

        if (method_exists($file, 'getLocalPath')) {
            return $file->getLocalPath();
        }

        return null;
    }

    /**
     * (Down)load a file from path or url and store it as File/Image asset object
     *
     * @param string $filePathOrUrl URL to internet file or path to local file (direct or with 'file:' prefix)
     * @param string|null $assetPath folder/filename path in asset store (e.g. 'Imports/my-file.txt')
     * @param bool $publish whether to publish the file after import
     * @param string $conflictResolution AssetStore conflict strategy (default: CONFLICT_OVERWRITE)
     * @return File|Image|null
     * @throws Exception
     */
    public static function import_file_asset(
        string $filePathOrUrl,
        string $assetPath = null,
        bool $publish = true,
        string $conflictResolution = AssetStore::CONFLICT_OVERWRITE
    ) {
        return self::download_and_save_asset($filePathOrUrl, $assetPath, $publish, $conflictResolution);
    }

    // Download/load a file into assets (set to private and replaced with add_file_to_assets in order to phase out $write argument)
    private static function download_and_save_asset(
        $filePathOrUrl,
        $assetPath = null,
        $publish = true,
        $conflictResolution = AssetStore::CONFLICT_OVERWRITE
    ) {
        // fallback to just filename of original file
        $fileName = basename($assetPath ?: $filePathOrUrl);
        $fileExt = File::get_file_extension($fileName);
        $dirPath = ltrim(dirname(trim($assetPath ?? '', '/')), '.'); // copied from Folder::find_or_make
        $assetPath = implode(DIRECTORY_SEPARATOR, array_filter([$dirPath, $fileName]));

        // Check if allowed file type
        if(!in_array($fileExt, File::getAllowedExtensions())){
            throw new Exception("FILE EXTENSION NOT ALLOWED IN ASSETS ({$fileExt})");
        }

        $File = File::find($assetPath);
        if(!$File) {
            $File = File::get_app_category($fileExt) == 'image' ? Image::create() : File::create();
        }

        # Track whether we created a temp file (for cleanup)
        $tempFilePath = null;

        // Create pointer to (temp) local file
        if(strpos($filePathOrUrl, 'file:')===0) {
            $localFilePath = '/' . str_replace(['file:///', 'file://', 'file:/', 'file:'], '', $filePathOrUrl);
        } elseif (is_file($filePathOrUrl)) {
            $localFilePath = $filePathOrUrl;
        } else {
            # Download URL to temp file — pass path string (not handle) so Guzzle manages open/close
            $client = new Client();
            $tempFilePath = tempnam(sys_get_temp_dir(), 'asset');
            $client->request('GET', $filePathOrUrl, ['sink' => $tempFilePath]);
            $localFilePath = $tempFilePath;
        }

        if (!is_file($localFilePath) || filesize($localFilePath) === 0) {
            # Clean up temp file before throwing
            if ($tempFilePath) {
                @unlink($tempFilePath);
            }
            throw new Exception("Source file is missing or empty: {$filePathOrUrl}");
        }

        try {
            // setFromLocalFile assigns content to the asset store backend
            $File->setFromLocalFile($localFilePath, $assetPath, null, null, [
                'conflict' => $conflictResolution,
            ]);

            # Verify content was actually stored (setFromLocalFile can fail silently)
            if (!$File->getHash()) {
                throw new Exception("setFromLocalFile() failed to store content for: {$assetPath}");
            }

            // ->generateThumbnails for asset manager (for some reason only works after first ->write())
            $File->write();
            AssetAdmin::singleton()->generateThumbnails($File);

            if($publish) {
                $File->publishRecursive();
            }
        } finally {
            # Always clean up temp files
            if ($tempFilePath) {
                @unlink($tempFilePath);
            }
        }

        return $File;
    }

    /**
     * Perform a HTTP request via Guzzle and return the response
     *
     * @param $reqUrl
     * @param string $reqMethod
     * @param array $options
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

        } catch ( GuzzleException $exception ) {
            throw new Exception("GUZZLE EXCEPTION [{$exception->getCode()}]: {$exception->getMessage()}");
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
