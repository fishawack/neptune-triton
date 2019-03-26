<?php
/**
 * Triton plugin for Craft CMS 3.x
 *
 * JSON export for Neptune
 *
 * @link      www.fishawack.com
 * @copyright Copyright (c) 2018 GeeHim Siu
 */

namespace fishawack\triton\controllers;

use fishawack\triton\Triton;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\elements\Category;

ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

/**
 * CustomMethodController
 * ==
 *
 * There are times when we need functions for one off actions, declare and use them here
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    GeeHim Siu
 * @package   Triton
 * @since     1.0.0
 */
class CustomMethodController extends Controller
{
    /**
     *  Grab all the publications from DB and lay the json
     *  data correctly
     *
     * @return json
     */
    public function actionConvertKogenate()
    {
        //$category = Category::find()->slug('kogenate');
        //$entries = Entry::find()
        //    ->section('publications')
        //    ->relatedTo($category)
        //    ->all();

        //$items = [];
        //foreach($entries as $entry)
        //{
        //    //$attr = [
        //    //    'product' => array('Kogenate')
        //    //];

        //    //$newEntry = Craft::$app->elements->duplicateElement($entry, $attr);
        //    //$newId = $newEntry->id;

        //    //$updateEntry = Entry::find()->id($newId)->one();
        //    //$updateEntry->product = 'Kogenate';
        //    //if(!Craft::$app->elements->saveElement($updateEntry)) {
        //    //    throw new \Exception("Saving failed: " . print_r($updateEntry->getErrors(), true));
        //    //}
        //    var_dump($entry->title);
        //}

        /* Testing tables */
//        $fileLinks = Entry::find()->title('K-00064')->one();
//        foreach($fileLinks->downloadLink as $link)
//        {
//            var_dump($link);
//        }
//
        $downloadLinksTable = Entry::find()->title('K-00054')->one();

        $links = [
            [
                'col1' => 'testlink1.pdf',
                'downloadLink' => 'testlink1.pdf',
            ],
            [
                'col1' => 'testlink2.html',
                'downloadLink' => 'testlink2.html'
            ]
        ];

        $downloadLinksTable->downloadLink = $links;
        if(!Craft::$app->elements->saveElement($downloadLinksTable)) {
            throw new \Exception("Saving failed: " . print_r($downloadLinksTable->getErrors(), true));
        }
        
        die();
    }

    public function actionChangeLinksToTables()
    {
        $allEntries = Entry::find()->section('publications')->all();
        foreach($allEntries as $entry)
        {
            if(isset($entry->prevDownload))
            {

                $files = explode(';', (string)$entry->prevDownload);
                $list = [];
                foreach($files as $index => $file)
                {
                    if($index === 0 && strpos($file, '.pdf') === false) 
                    {
                        $file = $file . '.pdf';
                    }

                    $list[] = [
                        'col1' => trim($file, '_'),
                        'file' => trim($file, '_'),
                    ];
                }

                $entry->file = $list;

                if(!Craft::$app->elements->saveElement($entry)) {
                    throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
                }
            }
        }

        var_dump('finished');
    }

    public function actionClearEmptyFiles() 
    {

        $entries = Entry::find()->section('publications')->all();

        foreach($entries as $entry) 
        {
            $files = [];

            if($entry->file)
            {
                foreach($entry->file as $file)
                {
                    //var_dump($file['file']);
                    if($file['file'] !== '')
                    {
                        $files[] = $file; 
                    }

                    $entry->file = $files;
//                    var_dump($entry->title);

                    if(!Craft::$app->elements->saveElement($entry)) {
                        throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
                    }
                }

            }
        }

//        $allEntries = Entry::find()->section('publications')->all();
//        foreach($allEntries as $entry)
//        {
//            if(isset($entry->file))
//            {
//                foreach($entry->file as $index => $item)
//                {
//                    if($item['file'] === '.pdf.pdf' || $item['file'] === '.pdf')
//                    {
//                        $entry->file = [];
//                    }
//
//                    if((strpos($item['file'], '.pdf.pdf') !== false))
//                    {
//                    }
//                }
//
//                if(!Craft::$app->elements->saveElement($entry)) {
//                    throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
//                }
//            }
//        }

        var_dump('finished');
    }
}
