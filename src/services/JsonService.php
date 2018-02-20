<?php

/*
 *
 *
 */

namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use yii\base\Component;
use craft\helpers\UrlHelper;

// Make sure allow url fopen is enabled
ini_set("allow_url_fopen", 1);

class JsonService extends Component
{
    public function updateJsonFile($section)
    {
        // Get SiteUrl
        $siteInfo = new UrlHelper;

        $allVarJson = Triton::getInstance()->variablesService->getJsonLinks();
        if(!isset($allVarJson[$section]))
        {
            $result['error'] = 'No section with this data name';
            return $result;
        }

        // Check folder will handle the exceptions
        // as well
        $this->checkFolder();

        $fileData = $allVarJson[$section];

        $data = '';
        switch($section)
        {
            case 'publications':
                $data = $this->getAllPublications();
                break;
            case 'studies':
                $data = $this->getAllStudies();
                break;
            case 'journals':
                $data = $this->getAllJournals();
                break;
            case 'congresses':
                $data = $this->getAllCongresses();
                break;
            case 'tags':
                $data = $this->getAllTags();
                break;
        }

        $data = json_encode($data);

        if(!$this->writeJsonFile($data, $fileData['path']))
        {   
            $result['error'] = "Something went wrong!";    
        }

        $result['complete'] = "'".$section."' cache has been successfully updated";
        return $result;
    }

    /*
     *  Check if folder/file exists,
     *  if not create it
     *
     *  @param string $filepath
     */
    protected function checkFolder()
    {
        if(!file_exists('json/'))
        {
            mkdir('json/', 0755);
        }
    }

    /*
     *  Write json data to file
     *
     *  @param 
     */
    protected function writeJsonFile($data, $filePath)
    {
        $outputToFile = fopen($filePath, 'w');
        if($outputToFile === false)
        { 
            throw new \Exception('Cannot open file');
        }

        $status = fwrite($outputToFile, $data);

        if($status === false)
        {
            throw new \Exception('Something went wrong with writing file to Disk!');
        }

        return true;
    }

    /*
     * Get Data formatted
     *
     * Setting single to true will put the
     * array data out of an array, this is only 
     * used when you have a single item in the 
     * $craftEntries array
     *
     * @param array $craftEntries
     * @param array $jsonStructure
     * @param bool $single
     * @param bool $children
     */
    public function getSectionDataFormatted(array $craftEntries, array $jsonStructure, $single = false, $children = false)
    {
        $dataArray = [];

        foreach($craftEntries as $entry)
        {
            $structure = &$jsonStructure;

            $entryId = (int)$entry->id;

            foreach($structure as $key => $value)
            {
                if(isset($entry->$value))
                {
                    // We need to check for data needs to be // filtered i.e Dates / booleans / arrays.
                    // These need seperate preparation.
                    if(is_a($entry->$value, 'DateTime'))
                    {
                        if($single == false)
                        {
                            $dataArray[$entryId][$key] = $entry->$value->format('Y-m-d');
                        } else {
                            $dataArray[$key] = $entry->$value->format('Y-m-d');
                        }
                    } elseif(is_a($entry->$value, 'craft\elements\db\EntryQuery') || is_a($entry->$value, 'craft\elements\db\CategoryQuery')) {
                        foreach($entry->$value as $newVal)
                        {
                            /**
                             *  Bit annoying here - 
                             *  Some categories etc has a nesting system which
                             *  means that Mike needs both ID and Title whilst the
                             *  rest only needs a set of Ids
                             */
                            if($single == false && $children)
                            {
                                $dataArray[$entryId][$key]['id'] = (int)$newVal->id;
                                $dataArray[$entryId][$key]['title'] = (string)$newVal->title;

                            } elseif($single == false) {
                                $dataArray[$entryId][$key][] = (int)$newVal->id;
                            } else {
                                $dataArray[$key][] = (int)$newVal->id;
                            }
                        }
                    } else {
                        if($entry->$value !== null)
                        {
                            // Some craft object fields require you
                            // to typecast to string before you get
                            // the value
                            $data = (string)$entry->$value;

                            // Check if the string has any characters,
                            // if so they it's supposed to be a string else
                            // we need to change it back to a integer for Mikes
                            // front end build
                            if(!preg_match("/[a-z]/i", $data) && strlen($data) >= 1)
                            {
                                $data = (int)$data;

                                // Only fields that are
                                // boolean will have the numbers
                                // 1 & 0 so we need to transform
                                // these back to bools
                                switch($data)
                                {
                                    case 1:
                                        $data = true;
                                        break;
                                    case 0:
                                        $data = false;
                                        break;
                                }
                            }
                            if($single == false)
                            {
                                $dataArray[$entryId][$key] = $data;
                            } else {
                                $dataArray[$key] = $data;
                            }
                        }
                    }
                }
            }
        }

        return $dataArray;
    }
}
