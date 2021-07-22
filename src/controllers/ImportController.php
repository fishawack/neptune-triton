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
use craft\web\UploadedFile;
use craft\web\Request;
use craft\elements\Asset;
use craft\elements\Entry;

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

        /*
         * Get the product name from POST
         */
        $product = '';
        if(isset($_POST['product']))
        {
            $product = (string)$_POST['product'];
        }

        if($uploadedFile)
        {
            $uploadResult = Triton::getInstance()->tritonAssets->saveAsset($uploadedFile, $folderId);
            // Get our path to Asset
            $csvPath = Triton::getInstance()->tritonAssets->getAssetPath($uploadResult); 

            // Append data to pubs
            if(isset($_POST['append']))
            {
                $importData = Triton::getInstance()->csvService->readCsvIntoArray($csvPath);
                $results = Triton::getInstance()->entryService->appendDataToPubs($importData, $product);
            } else {
                if(isset($_POST['custom_csv'])) {
                    $importData = Triton::getInstance()->csvService->readCsvIntoArray($csvPath);
                    $results = Triton::getInstance()->entryService->excelToCsv($importData, $product);
                } else {
                    $csvType = Triton::getInstance()->csvService->checkCsvType($csvPath);
                    switch ($csvType)
                    {
                    case "Publications":
                        $importData = Triton::getInstance()->csvService->publicationCsvToArray($csvPath);
                        $results = Triton::getInstance()->entryService->importArrayToEntries($importData, $product);
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
                }
            }

            if(!isset($results) || !$results)
            {
                $results['error'] = "The import was unsuccessful!";
            }
        } else {
            $results['error'] = "Please upload a file!";
        }

        $performance = microtime(true) - $scriptStart;

        // Export results to file
        if(!isset($results['error'])) {
            Triton::getInstance()->csvExportService->exportTxt('csvexport/'.$csvType.'.txt', $results);
        }

        return $this->renderTemplate('triton/importchanges', [
            'results' => $results,
            'performance' => $performance
        ]);
    }
}
