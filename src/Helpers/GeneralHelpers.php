<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;
use GuzzleHttp\Client;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
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

    public static function download_and_save_asset($fileUrl, $assetDir, $fileName, $write=true, $publish=true)
    {
        // Check if allowed file type
        $fileExt = File::get_file_extension($fileName);
        if(!in_array($fileExt, File::getAllowedExtensions())){
            throw new Exception("FILE EXTENSION NOT ALLOWED IN ASSETS ({$fileName})");
        }

        $Folder = Folder::find_or_make($assetDir);
        $fileRelativePath = $Folder->getFilename() . DIRECTORY_SEPARATOR . $fileName;
        $fileAbsolutePath = ASSETS_PATH . DIRECTORY_SEPARATOR . $fileRelativePath;
        $File = File::find($fileRelativePath);
        if(!$File) {
            $File = File::get_app_category($fileExt) == 'image' ? Image::create() : File::create();
        }

        // Download to temp file
        $client = new Client();
//        $tmpFile = tmpfile();
//        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];
        $tmpFile = tempnam(sys_get_temp_dir(), 'asset');
//        $client->request('GET', $fileUrl, ['sink' => $fileAbsolutePath]);
        $client->request('GET', $fileUrl, ['sink' => fopen($tmpFile, 'w')]);

        // Create & save to File https://api.silverstripe.org/4/SilverStripe/Assets/File.html#method_setFromLocalFile
        // setFromLocalFile(string $path, string $filename = null, string $hash = null, string $variant = null, array $config = []) Assign a local file to the backend.
        // setFromStream(resource $stream, string $filename, string $hash = null, string $variant = null, array $config = []) Assign a stream to the backend
        // setFromString(string $data, string $filename, string $hash = null, string $variant = null, array $config = []) Assign a set of data to the backend
        $File->setFromLocalFile($tmpFile, $fileName, null, null, [ 'conflict' => AssetStore::CONFLICT_OVERWRITE, ]);

        $File->Title = $fileName;
        $File->ParentID = $Folder->ID;
        $File->setFilename($fileRelativePath);

        // This is needed to build the thumbnails in asset manager (doesnt seem necessary?)
        AssetAdmin::create()->generateThumbnails($File);

        if($write) {
            $File->write();
        }
        if($publish) {
            $File->publishRecursive();
        }

        return $File;
    }
}
