<?php

/*
 *
 */
namespace fishawack\triton\services;

use fishawack\triton\Triton;

use Craft;

use yii\base\Component;
use craft\elements\Entry;
use craft\fields\Entries as BaseField;

ini_set('xdebug.var_display_max_depth', 1000);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
ini_set('max_execution_time', 300);

/**
 *  Entry Controller
 *
 *  @author GeeHim Siu
 *  @package Triton
 */
class EntryService extends Component
{
    private $data = [],
        $publicationTitle = [],
        $sectionId,
        $entryType,
        $authorId;

    /*
     *  Have a list of the data that needs to be
     *  imported - if it needs updating/creating do it
     *  then remove from the array.
     *
     *  The array will finish with a list a titles that
     *  need to be deleted (or disabled), so do that 
     *  as well :)
     */
    public function importArrayToEntries(array $data)
    {
        $this->data = $data;
        $allPublications = $this->getAllEntries();

        // Get all publications
        $this->setupPublicationTitles($allPublications);

        // Check if there's any changes, if not add new entry
        foreach($data as $entry)
        {
            if(isset($allPublications[$entry['title']]))
            {
                $this->prepareAndSaveEntry($entry, $allPublications[$entry['title']]);

                // delete from array so that we're
                // left with publications that have been
                // deleted.
                unset($allPublications[$entry['title']]);
            } else {
                //$this->newEntry($entry);
            }
        }

        // If anything is left in the array then we
        // need to delete(disable) these records
        if(count($allPublications) > 0)
        {
            foreach($allPublications as $deletedEntry)
            {
                $this->deleteEntry($deletedEntry);
            }
        }

        return Triton::getInstance()->entryChangeService->getStatus();    
    }

    /**
     * Get all entries from publications
     */
    public function getAllEntries()
    {
        $publication = [];        
        $currentUser = Craft::$app->getUser()->getIdentity();

        $queryPublications = Entry::find()
            ->section('publications')
            ->all();

        // We just need 1 entry as a base to
        // grab the information we need
        $this->sectionId = $queryPublications[0]->sectionId;
        $this->entryType = $queryPublications[0]->type;
        $this->authorId = $currentUser->id;

        foreach($queryPublications as $publication)
        {
            $publications[$publication->title] = $publication;
        }
        return $publications;
    }

    /*
     * @param Entry 
     */
    protected function setupPublicationTitles($allPubs)
    {
        foreach ($allPubs as $pub)
        {
            $this->publicationTitle[] = $pub->title; 
        }
    }

    /*
     *  Get all entrie titles from publications
     */
    protected function getAllEntryTitles()
    {
        $queryAll = Entry::find()
        ->section('publications')
        ->all();
    }

    /*
     *  Prepare and save the data,
     *  in preparation we're checking
     *  if there's been any changes
     *
     *  @param array $csvData
     *  @param Entry $craftData
     */
    protected function prepareAndSaveEntry(array $csvData, Entry $craftData)
    {
        //die(var_dump($csvData));
        $pubFields = $this->getPublicationArrayFields();

        /*
         * Save our studies seperately!
         *
         * Without this there will be a foreign Key error,
         * once the save has been done then we can remove the
         * studies from our original array
         *
         * Annoyingly saveRelation doesn't tell you if
         * it has been saved or not, it'll always return null
         */
        $saveStudy = Triton::getInstance()->studiesService->saveStudyRelation($csvData['study'], $craftData);
        unset($csvData['study']);

        /**
         * Save Journal/Congress
         *
         * TODO
         * Same theory applies to these fields as well.
         */

        unset($csvData['journal']);
        unset($csvData['title']);

        // remove title from our pubFields
        // since we've already retrieved them
        unset($pubFields[0]);
        unset($pubFields[6]);
        unset($pubFields[11]);

     
        /**
         *  Track Changes
         */
        $changed = 0;
        foreach($pubFields as $data)
        {
            // There maybe whitespace
            $data = trim($data);

            // Check if it's a date time class
            // and do the necessary comparison
            if(is_a($craftData[$data], 'DateTime')) 
            {
                $csvDate = new \DateTime($csvData[$data]);
                $csvDate = $csvDate->getTimestamp();
                $craftTime = $craftData->$data->getTimestamp();
                
                // change CraftEntry datetime for comparison
                if($csvDate !== $craftTime)
                {
                    $changed++;
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $data);
                }
            } else {
                if((string)$csvData[$data] !== (string)$craftData->$data)
                {
                    $changed++;
                    // Add change to the service for later use
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $data);
                }
            }
        }

        if($changed === 0)
        {
            Triton::getInstance()->entryChangeService->addUnchanged($craftData->title);
        }

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($csvData);

        $status = Triton::getInstance()->entryChangeService->getStatus();

        if(Craft::$app->elements->saveElement($craftData)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($craftData>getErrors(), true));
        }
    }

    /*
     * Check if the entry exists already in
     * our CMS
     */
    protected function checkEntry($title)
    {
        if(in_array($title, $this->data))
        {
            return $this->data[$title];
        }
        return false;
    }

    /*
     *
     * Delete Entry
     *
     */
    public function deleteEntry(Entry $craftEntry)
    {
        Triton::getInstance()->entryChangeService->addDeletedEntry((string)$craftEntry->title);

        $craftEntry->enabled = '0';
        if(Craft::$app->elements->saveElement($craftEntry)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
    }

    /**
     *  Save all object data into array
     *  so that it can be compared
     */
    protected function newEntry(Array $csvData)
    {
        $entry = new Entry();

        // Set all variables needed to save an 
        // entry
        $entry->sectionId = $this->sectionId;
        $entry->authorId = $this->authorId;
        $entry->typeId = $this->entryType->id;

        $entry->title = $csvData['title'];
        $entry->slug = str_replace(' ', '-', $csvData['title']);

        // New way of seting fields, need to remove our title
        // for set fields to work
        unset($csvData['title']);

        $entry->setFieldValues($csvData);

        //var_dump($entry);
        //die();

        if(Craft::$app->elements->saveElement($entry)) {
            die();
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
        die();

        $craftEntry->study = $studies;

        return $entry;
    }

    protected function getPublicationArrayFields()
    {
        $pubFields = [
            'title',
            'documentTitle',
            'documentStatus',
            'startDate',
            'submissionDate',
            'documentAuthor',
            'journal',
            'documentType',
            'citation',
            'citationUrl',
            'publicationDate',
            'study'    
        ];
        return $pubFields;
    }
}
