<?php
/**
 * Triton plugin for Craft CMS 3.x
 *
 * Import CSV file to craft
 *
 * @link www.fishawack.com
 * @copyright Copyright (c) 2018 GeeHim Siu
 *
 */

namespace fishawack\triton\controllers;

use fishawack\triton\Triton;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\web\UploadedFile;
use craft\elements\Asset;
use craft\helpers\Assets as AssetHelper;

use yii\web\BadRequestHttpException;
use yii\web\UploadFailedException;
use yii\web\UnsupportedMediaTypeHttpException;

/**
 * Import Controller
 *
 * @author GeeHim Siu
 * @package Triton
 */
class ImportController extends Controller
{
    protected $allowAnonymous = true;
    /*
     *  Upload CSV file to folder
     */
    public function actionInitImport()
    {
        // Set up variables
        // ================
        //
        // folderid for asset folder
        $folderId = '1';

        /*
         *  Setup our file by saving into the temp
         *  folder and moving it into our asset
         *  library
         */
        $uploadedFile = UploadedFile::getInstanceByName('tritonupload');

        if($uploadedFile)
        {
            $uploadResult = Triton::getInstance()->tritonAssets->saveAsset($uploadedFile, $folderId);
            // Get our path to Asset
            $csvPath = Triton::getInstance()->tritonAssets->getAssetPath($uploadResult); 

            // Read the file into memory
            $importData = Triton::getInstance()->csvService->csvToArray($csvPath);

            $results = Triton::getInstance()->entryService->importArrayToEntries($importData);
            
            if(!$results)
            {
                $results['error'] = "The import was unsuccessful!";
            }
        } else {
            $results['error'] = "Please upload a file!";
        }

        return $this->renderTemplate('triton/importchanges', [
            'results' => $results
        ]);
    }
}
