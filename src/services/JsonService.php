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
    /*
     * @param array $data
     * @param string $section
     */
    public function updateJsonFile($data, $section)
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

        // TODO
        // Look into implementing something here instead
        // of just patching, only globals is an object(JS)
        // everything else is an array
        if($section === 'globals')
        {
            foreach($data as $jsObject)
            {
                $newData = $jsObject;
            }
            $jsonData = json_encode($newData);
        } else {
            $jsonData = json_encode(array_values($data));
        }

        if(!$this->writeJsonFile($jsonData, $fileData['path']))
        {   
            $result['error'] = "Something went wrong!";    
        }

        $result['complete'] = "'".$section."' cache has been successfully updated";
        return $result;
    }

    /**
     *  Find what function corresponds to the data
     *  i.e. $data = publications, we need to get our
     *  variable list and pull up the functions that are 
     *  available
     *
     *  @param string $data
     */
    public function findFunctionForData(string $data)
    {
        $functions = Triton::getInstance()->variablesService->getJsonCacheFunctionsStruc();

        if(!isset($functions[$data]))
        {
            return false;
        }

        return $functions[$data];
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
     *  Since the categories work differently
     *  to entries, this seperate function is 
     *  to deal with that.
     *
     *  @param
     */
    public function getCategoryDataFormatted($craftCategories, array $jsonStructure)
    {
        $dataArray = [];
        foreach($craftCategories as $categoryData)
        {
            //var_dump($categoryData);
            if(!$categoryData->parent) 
            {
                foreach($jsonStructure as $key => $value)
                {
                    if(is_array($categoryData[$value]) || is_a($categoryData[$value], 'craft\elements\db\CategoryQuery'))
                    {
                        foreach($categoryData[$value] as $data)
                        {
                            $dataArray[$categoryData->id][$value][] = [
                                'id' => (int)$data->id,
                                'title' => (string)$data->title
                            ];
                        }
                    } else {
                        $dataArray[$categoryData->id][$value] = $categoryData->$value;
                    }
                }
            }
        }
        return $dataArray;
    }

    /*
     * Get Data formatted
     *
     * Setting single to true will put the
     * array data out of an array, this is only 
     * used when you have a single item in the 
     * $craftEntries array.
     *
     * This function will take our craft entry and
     * the variable json structure set in 
     * variablesService to create the json file needed
     *
     * -------------------------------------
     *
     * How does it work?
     *
     * The json file sets out all the variables
     * that you would like to get from the entry, i.e.
     * relatedPubs etc, this means better code reusablility
     * we can put in a structure for another plant implementation
     * that doesn't use the same structure i.e. it might have
     * projectCode instead
     *
     * @param array $craftEntries
     * @param array $jsonStructure
     * @param bool $single
     * @param bool $children
     */
    public function getSectionDataFormatted(array $craftEntries, array $jsonStructure, $single = false, $children = false)
    {
        // Get list of all categories
        // ==========================
        //
        // Fields can also be assigned to categories,
        // we'll use this list to check out variables
        // and also to keep a record of all the ids
        $categories = Triton::getInstance()->queryService->queryAllCategories();
        $dataArray = [];

        foreach($craftEntries as $entry)
        {
            $structure = &$jsonStructure;

            $entryId = (int)$entry->id;

            /*
             * Go through our Json structure and match
             * the values to our craft entry
             */
            foreach($structure as $key => $value)
            {
                if(is_array($value))
                {
                   foreach($value as $newValue)
                    {
                        // Get out function as a string
                        $customFunction = $newValue['function'];

                        // Setup our variable
                        $craftName = $newValue['craftName'];
                        $jsonName = $newValue['jsonName'];
                        $craftHandle = $entry->$craftName;
                        $result = Triton::getInstance()->jsonCustomService->$customFunction($craftHandle);
                        $dataArray[$entryId][$jsonName] = $result;
                    }
                } elseif(isset($entry->$value))
                {
                    // Check that the option is a dropdown or single
                    // option as craft calls it, also check to make sure
                    // that it's attached to category
                    if(is_a($entry->$value, 'craft\fields\data\SingleOptionFieldData') && isset($categories[(string)$entry->$value]))
                    {
                        $data = $categories[(string)$entry->$value]->id;

                        $dataArray[$entryId][$key] = (int)$data;

                    } elseif(is_a($entry->$value, 'DateTime')) {
                        // We need to check for data needs to be // filtered i.e Dates / booleans / arrays.
                        // These need seperate preparation.
                        if($single == false)
                        {
                            $dataArray[$entryId][$key] = $entry->$value->format('Y-m-d');
                        } else {
                            $dataArray[$key] = $entry->$value->format('Y-m-d');
                        }
                    } elseif(is_a($entry->$value, 'craft\elements\db\EntryQuery') || is_a($entry->$value, 'craft\elements\db\CategoryQuery')) {

                        /*
                         *  get all entries even if they're disabled
                         */
                        $entry->$value->status(null)->find();
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
                                $dataArray[$entryId][$key][] = [
                                    'id' => (int)$newVal->id,
                                    'title' => (string)$newVal->title,
                                ];
                            } elseif($single == false) {
                                $dataArray[$entryId][$key][] = (int)$newVal->id;
                            } else {
                                $dataArray[$key][] = (int)$newVal->id;
                            }
                            
                            /*
                             * Benlysta specific code - we need
                             * a list of the disabled publications
                             * so that we can merge them in
                             */
                            if($value === 'journal' || $value === 'congress')
                            {
                                $dataArray[$entryId][$key] = (int)$newVal->id;
                            }
                        }
                    } elseif(is_array($entry->$value)) {
                        /*
                         * If the field is a table     
                         */
                        foreach($entry->$value as $index => $item) {
                            $dataArray[$entryId][$key][] = $item[$value];
                        }

                        //$dataArray[$entryId][$key][] = $entry->$value[$value];
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

            // Do some custom filtering via our
            // custom method
            $dataArray[$entryId] = Triton::getInstance()->jsonCustomService->filterArray($dataArray[$entryId]);
        }

        return $dataArray;
    }
}
