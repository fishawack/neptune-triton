<?php

/*
 * JSC Import Service
 * ==================
 *
 * Since our import plugin revolves around
 * Publication entries, everything else
 * has a much simpler interface therefore we
 * can group them together in this class.
 *
 * J - Journals
 * S - Studies
 * C - Congress
 */
namespace fishawack\triton\services;

use fishawack\triton\Triton;

use Craft;

use yii\base\Component;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\db\EntryQuery;
use craft\fields\Entries as BaseField;
use craft\helpers\ElementHelper as Helper;

Class JSCImportService extends component
{
    private $sectionId,
        $entryType,
        $authorId,
        $typeId,
        $sectionTitle = '',
        $JSCObjects = [];

    public function __construct()
    {
        $getEntries = Triton::getInstance()->queryService->queryAllEntries($this->sectionTitle);
        //$this->sectionId = $getEntries[0]->sectionId;
        //$this->entryType = $getEntries[0]->type;

        if(!isset(Craft::$app->getUser()->getIdentity()->id))
        {
            throw new \Exception("Please login as admin");
        }
        $currentUser = Craft::$app->getUser()->getIdentity()->id;
        $this->authorId = $currentUser;

        $this->JSCObjects = Triton::getInstance()->queryService->swapKeys($getEntries);
    }

    public function setJSCObjects(string $sectionTitle)
    {
        $getEntries = Triton::getInstance()->queryService->queryAllEntries($sectionTitle);
        $this->sectionId = $getEntries[0]->sectionId;
        $this->entryType = $getEntries[0]->type;
        $this->authorId = $currentUser = Craft::$app->getUser()->getIdentity()->id;
        
        $this->JSCObjects = Triton::getInstance()->queryService->swapKeys($getEntries);     
    }

    /**
     *  Get the study field
     *
     *  @param Entry $craftEntry
     */
    public function getJSCField(Entry $craftEntry, string $handle)
    {
        $fields = $craftEntry->getFieldLayout()->getFields();
        $studyField = 0;
        foreach($fields as $field)
        {   
            if($field->handle == $handle)
            {
                $studyField = $field;
            }
        }

        return $studyField;
    }

    /**
     *  CSV Import
     *
     *  Get the cleaned and exploded array
     *
     *  @param string $sectionTitle
     *  @param array $jscEntries
     */
    public function importArrayToEntries(string $sectionTitle, array $jscEntries)
    {
        /* Problematic entry for BPubs will look later*/
        /*$test = Entry::find()->section($sectionTitle)->search("National Conference on Management, Economics and Policies of Health - 9th")->one();
        die();*/
        $this->sectionTitle = $sectionTitle;

        // Constructor doesn't construct
        // when accessing the class through 
        // services weirdly.
        $this->setJSCObjects($sectionTitle);

        // Get list of studies already in the system
        $jscList = $this->JSCObjects;

        // Check if there's any changes, if not add new entry
        foreach($jscEntries as $entry)
        {
            $find = Entry::find()
                ->section($sectionTitle)
                ->title($entry['title'])
                ->one();

            if($find && isset($this->JSCObjects[$entry['title']]) && $find->title === $entry['title'])
            {
                $find->title = trim($find->title);
                $this->saveExisting($sectionTitle, $entry, $this->JSCObjects[$find->title]);
            } else {
                // Seems like some records we have to use another search, just some stray records
                // which we cannot search via ->title()
                $extendedFind = Entry::find()
                ->section($sectionTitle)
                ->search($entry['title'])
                ->one();

                if($extendedFind && $extendedFind->title === $entry['title']) {
                    $extendedFind->title = trim($extendedFind->title);
                    $this->saveExisting($sectionTitle, $entry, $this->JSCObjects[$extendedFind->title]);
                } else {
                    $this->saveNewJSC($entry['title'], $entry, true);
                    Triton::getInstance()->entryChangeService->addNewEntry($entry['title']);
                }
            }

            // delete from array so that we're
            // left with publications that have been
            // deleted.
            unset($jscList[$entry['title']]);
        }

        // If anything is left in the array then we
        // need to delete(disable) these records
        //
        // v1.1 No need to remove or disable any of our JSC
        /*
        if(count($jscList) > 0)
        {
            foreach($jscList as $deletedEntry)
            {
                Triton::getInstance()->entryService->deleteEntry($deletedEntry);
            }
        }*/

        return Triton::getInstance()->entryChangeService->getStatus();
    }

    /**
     *  Save studies that are already on the 
     *  system
     *
     *  @param string $sectionTitle
     *  @param string $handle
     *  @param array $jscData
     *  @param Entry $craftEntry
     */
    public function saveJSCRelation(string $sectionTitle, string $handle, array $jscData, Entry $craftEntry, $list = [], $createNew = false)
    {
        // Need to get section details
        $this->sectionTitle = $sectionTitle;
        $section = Triton::getInstance()->queryService->queryOneEntry($sectionTitle);

        // Set new section id
        // ------------------
        //
        // We need to set the section to whatever
        // our relation is. i.e. journals, if not
        // we will be saving our entries into publications
        $this->sectionId = $section->sectionId;
        $this->typeId = $section->type->id;
                
        $jscField = $this->getJSCField($craftEntry, $handle);

        $entryIds = [];
        foreach($jscData as $entry)
        {
            // Find if there's already an existing record
            $find = Entry::find()->section($sectionTitle)->search($entry)->one();

            if(!empty($jscData))
            {
                if($find && $find->title === $entry)
                {
                    // Add the found record as a relation
                    $entryIds[] = $find->id;
                } else {
                    // Extended find was still not as useful, changing to use slug!
                    //$extendedFind = Entry::find()->section($sectionTitle)->title($entry)->one();
                    //
                    $slug = Triton::getInstance()->queryService->changeTitleToSlug($entry);
                    $extendedFind = Triton::getInstance()->queryService->queryEntryBySlugAndSection($slug, $sectionTitle);

                    if($extendedFind && $extendedFind->title === $entry)
                    {
                        $entryIds[] = $extendedFind->id;
                    } else {
                        /* Save a the study as a new entry,
                         * find the studyId and put it into
                         * our list
                         *
                         * $result = $this->saveNewJSC($entry);
                         */

                        if($createNew)
                        {
                            /*
                             * Craft search is very weird, we definitely should have
                             * an ID when we save!!
                             */
                            $result = $this->saveNewJSC($entry);
                            if(!isset($result->id))
                            {
                                $searchAgain = Triton::getInstance()->queryService->queryEntryByTitle($entry);
                                $result = $searchAgain;
                            }

                            if($result)
                            {
                                $entryIds[] = $result->id;
                            } else {
                                Triton::getInstance()->entryChangeService->addMissingEntry($entry);
                            }
                        }

                        /*
                         * Changing the way this works, instead of creating a new
                         * entry we just list out the entries that couldn't be found
                         * and saved so that we can reimport them instead
                         */

                        /*
                         * There seems to be problem with finding 
                         * a certain entry within congresses, luckily
                         * using Entry::find we will get the latest
                         * entry. So if we have a problem looking for
                         * the specific entry, we will find it in another
                         * way
                         */

                        /* Disregard for now */
                        /*
                        if($createNew)
                        {
                            $getId = Entry::find()->section($sectionTitle)->search($entry)->one();

                            if(!$getId) {
                                $getId = Entry::find()->section($sectionTitle)->one();
                            }

                            if(isset($getId->id))
                            {
                                $entryIds[] = $getId->id;
                            }
                        }*/
                    }
                }
            }
        }

        $saveRelation = Craft::$app->relations->saveRelations($jscField, $craftEntry, $entryIds);
        return $saveRelation;
    }

    /**
     *
     * THis shouldn't be working however BPubs works,
     * requires more testing before using 
     *
     */
    public function saveCategoryRelation(string $handle, array $jscData, Entry &$craftEntry)
    {
        if(!empty($jscData)) {
            return false;
        }

        die(); 

        $jscField = $this->getJSCField($craftEntry, $handle);

        $categoryIds = [];

        $getCategory = Triton::getInstance()->queryService->queryCategoryById($craftEntry->$handle->groupId);
        // Swap Keys for easy searchign
        $categoryList = Triton::getInstance()->queryService->swapKeys($getCategory);
        
        foreach($jscData as $data)
        {
            if(isset($data) && $categoryList[$data])
            {
                $categoryIds[] = $categoryList[$data]->id;
            }
        }
        $saveRelation = Craft::$app->relations->saveRelations($jscField, $craftEntry, $categoryIds);
    }

    /*
     * Written for Bayer
     */
    public function saveCategoryRelations(string $handle, array $jscData, Entry $craftEntry) {
        if(empty($jscData)) {
            return false;
        }

        $jscField = Craft::$app->fields->getFieldByHandle($handle);

        $saveRelation = Craft::$app->relations->saveRelations($jscField, $craftEntry, $jscData);
    }

    /**
     * Check if items exists in Categories
     * 
     * Return a list of items that exists
     */
    public function checkCategoryItems(string $handle, array $items) 
    {
        $tags = Category::find()->group($handle)->asArray()->all();
        $tags = $this->getCategorySimpleItems($tags);
        $found = [];
        foreach($items as $item)
        {
            $item = trim($item);
            if(isset($tags[$item]))
            {
                $found[$item] = $tags[$item];
            }
        }

        return $found;
    }

    public function checkCategoryItem(string $handle, string $item) 
    {
        $tags = Category::find()->group($handle)->title($item);
        return $tags->id;
    }

    private function getCategorySimpleItems(array $categoryItems)
    {
        $simple = [];
        foreach($categoryItems as $item)
        {
            $simple[$item['title']] = $item['id'];
        }

        return $simple;
    }

    /**
     * Check if it's created via upload
     * csv or through publication import -
     * through Csv we need to build the 
     * relations
     *
     * @param string $studyData
     * @param bool $upload
     */
    public function saveNewJSC(string $jscTitle, array $jscData = [], bool $upload = false)
    {
        if($upload == true)
        {
            if(empty($jscData))
            {
                throw new \Exception("No values entered for studies");
            }

            $newJSC = new Entry();
 
            $newJSC->sectionId = $this->sectionId;
            $newJSC->typeId = $this->entryType->id;

            $newJSC->title = $jscTitle;
            $newJSC->slug = Triton::getInstance()->queryService->changeTitleToSlug($jscTitle);

            unset($jscData['title']);
            //unset($jscData['slug']);

            $newJSC->setFieldValues($jscData);

            if($saveResult = Craft::$app->elements->saveElement($newJSC)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newJSC->getErrors(), true));
            }
        } else {
            $newJSC = new Entry();
            
            $newJSC->sectionId = $this->sectionId;
            $newJSC->typeId = $this->typeId;

            $newJSC->title = $jscTitle;
            $newJSC->slug = Triton::getInstance()->queryService->changeTitleToSlug($jscTitle);

            if($saveResult = Craft::$app->elements->saveElement($newJSC)) {
                $slug = Triton::getInstance()->queryService->changeTitleToSlug($newJSC->title);
                return Triton::getInstance()->queryService->queryEntryBySlug($slug);
                //return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newJSC->getErrors(), true));
            }
        }
    }

    /*
     * Test adding categories
     */
    public function saveNewCategoryEntry(string $categoryHandle, string $item)
    {
        $categoryId = Triton::getInstance()->queryService->getCategory($categoryHandle)->id;
        //$category = Triton::getInstance()->queryService->getAllCategoriesUntouched($categoryHandle);

        $entryModel = new Category();
        $entryModel->groupId = $categoryId;
        $entryModel->title = trim($item);

        if(Craft::$app->elements->saveElement($entryModel)) {
            return $entryModel->id;
        }
    }

    /**
     * Save study and make sure the comparisons 
     * are returned
     *
     * @param array $studyData
     * @param Entry $craftData
     *
     */
    public function saveExisting(string $sectionTitle, array $data, Entry $craftData)
    {
        // Get list of study headers
        switch ($sectionTitle)
        {
            case 'studies':
                $headers = Triton::getInstance()->variablesService->getStudyHeaders();
                break;
            case 'journals':
                $headers = Triton::getInstance()->variablesService->getJournalHeaders();
                break;
            case 'congresses':
                $headers = Triton::getInstance()->variablesService->getCongressHeaders();
                break;
        }
        
        // Track changes
        $changed = 0;
        foreach($headers as $header)
        {
            // Check if it's a date time class
            // and do the necessary comparison
            if(is_a($craftData[$header], 'DateTime')) 
            {
                $date = new \DateTime($data[$header]);
                $date = $date->getTimestamp();
                $craftTime = $craftData->$header->getTimestamp();
                
                // change CraftEntry datetime for comparison
                if($date !== $craftTime)
                {
                    $changed++;
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            } else {
                if((string)$data[$header] !== (string)$craftData->$header)
                {
                    $changed++;
                    // Add change to the service for later use
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            }   
        }

        if($changed === 0)
        {
            Triton::getInstance()->entryChangeService->addUnchanged($data['title']);
            return true;
        }

        $craftData->title = $data['title'];
        unset($data['title']);

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($data);

        if(Craft::$app->elements->saveElement($craftData)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($craftData->getErrors(), true));
        }
    }
}
