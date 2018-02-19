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

ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

/**
 * Impor Controller
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
        // Script performation testing
        //
        // Method 1 = 192.3657
        // Method 2 = 185.3
        // Method 3 = 71.059
        $scriptStart = microtime(true);

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
            $csvType = Triton::getInstance()->csvService->checkCsvType($csvPath);

            switch ($csvType)
            {
                case "Publications":
                    $importData = Triton::getInstance()->csvService->publicationCsvToArray($csvPath);
                    $results = Triton::getInstance()->entryService->importArrayToEntries($importData);
                    break;
                case "Studies":
                     $importData = Triton::getInstance()->csvService->jscCsvToArray('studies', $csvPath);
                    $results = Triton::getInstance()->jscImportService->importArrayToEntries('studies', $importData);
                    break;
                case "Journals":
                    $importData = Triton::getInstance()->csvService->jscCsvToArray('journals', $csvPath);
                    $results = Triton::getInstance()->jscImportService->importArrayToEntries('journals', $importData);
                    break;
                case "Congress":
                    $importData = Triton::getInstance()->csvService->jscCsvToArray('congresses', $csvPath);
                    $results = Triton::getInstance()->jscImportService->importArrayToEntries('congresses', $importData);
                    break;
            }
                        
            if(!$results)
            {
                $results['error'] = "The import was unsuccessful!";
            }
        } else {
            $results['error'] = "Please upload a file!";
        }

        $performance = microtime(true) - $scriptStart;

        return $this->renderTemplate('triton/importchanges', [
            'results' => $results,
            'performance' => $performance
        ]);
    }
}
