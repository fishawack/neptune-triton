<?php

/**
 * Triton plugin for Craft CMS 3.x
 *
 * Our checker class, feel free to pipe through any tests here,
 * I've started with the duplicates tester
 *
 * @link      www.fishawack.com
 * @copyright Copyright (c) 2018 GeeHim Siu
 */

namespace fishawack\triton\controllers;

use fishawack\triton\Triton;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;


class CheckerController extends Controller 
{
    private $data;
    /**
     * Check for duplicates in data
     */
    public function actionDuplicates($section = '')
    {
        $delete = false;
        $showStatus = false;
        $sectionTitles = Triton::getInstance()->variablesService->getSectionTitles();
        
        if(isset($_GET['section']))
        {
            $section = $_GET['section'];
        }

        if(isset($_GET['status']))
        {
            $showStatus = true;
        }

        if(isset($_GET['delete_duplicates'])) 
        {
            $delete = true;
        }

        if($section === '' || !in_array($section, $sectionTitles))
        {
            $this->data['error'] = 'Please specify \'Section\' in params or section may not exist';
            return $this->asJson($this->data);
        }

        if($showStatus) {
            $duplicateEntries = Triton::getInstance()->checkerService->getAllDuplicatesWithStatus($section);
        } else {
            $duplicateEntries = Triton::getInstance()->checkerService->getAllDuplicates($section);
        }

        return $this->asJson($duplicateEntries);
    }

    public function actionDeleteDuplicates()
    {
        $selction = '';
        if(!isset($_GET['section']))
        {
            return $this->asJson(['Please specify the section']);
        }

        $section = (string)$_GET['section'];

        $results = Triton::getInstance()->checkerService->deleteDuplicates($section);

        return $this->asJson($results);
    }

    public function actionTest() 
    {
        $allPubs = Triton::getInstance()->queryService->getAllEntriesUntouched('publications');

        foreach($allPubs as $pub)
        {
            $pub->product = 'bpubs';
            if(Craft::$app->elements->saveElement($pub)) {
                continue;
            } else {
                throw new \Exception("Saving failed: " . print_r($pub->getErrors(), true));
            }
        }
        var_dump('Finished');
        die();
    }

    /*
     * Use this to clean titles i.ee trim, strip tags etc
     *
     */
    public function actionCleanTitles() 
    {
        $allJournals = Triton::getInstance()->queryService->getAllEntriesUntouched('journals');
        $allStudies = Triton::getInstance()->queryService->getAllEntriesUntouched('studies');
        $allCongress = Triton::getInstance()->queryService->getAllEntriesUntouched('congresses');

        foreach($allJournals as $journals)
        {
            $journals->title = trim($journals->title);
            if(Craft::$app->elements->saveElement($journals)) {
                continue;
            } else {
                throw new \Exception("Saving failed: " . print_r($journals->getErrors(), true));
            }
        }

        foreach($allStudies as $study)
        {
            $study->title = trim($study->title);
            if(Craft::$app->elements->saveElement($study)) {
                continue;
            } else {
                throw new \Exception("Saving failed: " . print_r($study->getErrors(), true));
            }
        }        
        
        foreach($allCongress as $congress)
        {
            $congress->title = trim($congress->title);
            if(Craft::$app->elements->saveElement($congress)) {
                continue;
            } else {
                throw new \Exception("Saving failed: " . print_r($congress->getErrors(), true));
            }
        }
        var_dump('Finished');
        die();
    }

    /**
     * 
     *
     * @return void
     */
    public function actionSetAllEntryProducts()
    {
        $json = [];

        if(!isset($_GET['productname']))
        {
            $message = 'No product name specified';
            $json['error'] = $message;
            return $this->asJson($json);
        }

        $productName = (string)$_GET['productname'];

        // Check if the product exists!
        $name = Triton::getInstance()->queryService->queryEntryByTitle($productName);

        if(!isset($name->title))
        {
            $message = 'Product doesn\'t exist!';
            $json['error'] = $message;
            return $this->asJson($json);
        }

        $name = (string)$name->title;

        $queryAll = Triton::getInstance()->queryService->getAllEntriesUntouched('publications');
    
        foreach($queryAll as $entry)
        {
            Triton::getInstance()->jscImportService->saveJSCRelation('products', 'product', (array)$name, $entry);
        }

        $json = [
            'Completed'
        ];

        return $this->asJson($json);
    }
    
}
