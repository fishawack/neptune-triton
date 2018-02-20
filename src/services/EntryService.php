<?php

/*
 *
 */
namespace fishawack\triton\services;

use fishawack\triton\Triton;

use Craft;

use yii\base\Component;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\fields\Entries as BaseField;

ini_set('xdebug.var_display_max_depth', 1000);

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
        $authorId,
        $studies,
        $journals,
        $congresses;

    /*
     *  Have a list of the data that needs to be
     *  imported - if it needs updating/creating do it
     *  then remove from the array.
     *
     *  The array will finish with a list a titles that
     *  need to be deleted (or disabled), so do that 
     *  as well :)
     *
     *  @param array $data
     */
    public function importArrayToEntries(array $data)
    {
        // Set the import date!
        $DVDate = GlobalSet::find()
            ->handle('datavisionExportDate')
            ->one();

        // If we don't have the import date setup 
        // then just skip this part
        if($DVDate)
        {
            $DVDate->DVDate = date('Y-m-d H:i:s');

            if(!Craft::$app->elements->saveElement($DVDate)) {
                throw new \Exception("Saving failed: " . print_r($DVDate->getErrors(), true));
            }
        }

        $this->data = $data;

        // Set this last so that we get the
        // correct sectionId
        $allPublications = $this->getAllEntries('publications');

        // Set sectionId, entryTypeId, authorId
        // grab the information we need, to do so we need
        // to get a random record from our publications
        $currentUser = Craft::$app->getUser()->getIdentity();
        $key = key($allPublications);
        $entryExample = $allPublications[$key];
        
        $this->sectionId = $entryExample->sectionId;
        $this->entryType = $entryExample->type;
        $this->authorId = $currentUser->id; 

        // Get all publications
        $this->setupPublicationTitles($allPublications);

        // Check if there's any changes, if not add new entry
        foreach($data as $entry)
        {
            if(isset($allPublications[$entry['title']])) 
            {
                // Check if the record is locked
                if($allPublications[$entry['title']]->lock === '1')
                {
                    Triton::getInstance()->entryChangeService->addLocked($entry['title']);
                } else {
                    $this->saveExisting($entry, $allPublications[$entry['title']]);
                }

                // delete from array so that we're
                // left with publications that have been
                // deleted.
                unset($allPublications[$entry['title']]);
            } else {
                $this->newEntry($entry);
                Triton::getInstance()->entryChangeService->addNewEntry($entry['title']);
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

        //die(var_dump($this->journals));

        return Triton::getInstance()->entryChangeService->getStatus();    
    }

    /**
     * Get all entries from publications
     *
     * @param string $entryHandle
     */
    public function getAllEntries(string $entryHandle)
    {
        $publication = [];        
        $currentUser = Craft::$app->getUser()->getIdentity();

        $queryPublications = Entry::find()
            ->section($entryHandle)
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
     *  Prepare and save the data,
     *  in preparation we're checking
     *  if there's been any changes
     *
     *  @param array $csvData
     *  @param Entry $craftData
     */
    protected function saveExisting(array $csvData, Entry $craftData)
    {
        //die(var_dump($csvData));
        $pubFields = Triton::getInstance()->variablesService->getPublicationHeaders();

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
        $saveStudy = Triton::getInstance()->jscImportService->saveJSCRelation('studies', 'study', $csvData['study'], $craftData, $this->studies);

        if(isset($csvData['journal']) && strlen($csvData['journal']) > 0)
        {
            Triton::getInstance()->jscImportService->saveJSCRelation('journals', 'journal', (array)$csvData['journal'], $craftData, $this->journals);
        }

        if(isset($csvData['congress']) && strlen($csvData['congress']) > 0)
        {
            Triton::getInstance()->jscImportService->saveJSCRelation('congresses', 'congress', (array)$csvData['congress'], $craftData, $this->congresses);
        }

        unset($csvData['study']);

        /**
         * Save Journal/Congress
         */
        unset($csvData['journal']);
        unset($csvData['congress']);
        unset($csvData['title']);

        // remove title from our pubFields
        // since we've already retrieved them
        unset($pubFields[0]);
        unset($pubFields[10]);
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
     *  Add a new Entry into Craft      
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

        // Setup relations to be imported
        $relations['studies'] = &$csvData['study'];

        if(isset($csvData['journal']))
        {
            $relations['journal'] = $csvData['journal'];
            unset($csvData['journal']);
        }

        if(isset($csvData['congress']))
        {
            $relations['congress'] = $csvData['congress'];
            unset($csvData['congress']);
        }
        
        // New way of seting fields, need to remove our title
        // for set fields to work
        unset($csvData['title']);
        unset($csvData['study']);

        $entry->setFieldValues($csvData);


        if($savedEntry = Craft::$app->elements->saveElement($entry)) {
            // Save Relationship with our other sections
            $getEntry = Entry::find()
                ->section('publications')
                ->one();

            Triton::getInstance()->jscImportService->saveJSCRelation('studies', 'study', $relations['studies'], $getEntry, $this->studies);

            if(!empty($relations['journal']))
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('journals', 'journal', (array)$relations['journal'], $getEntry, $this->journals);

            }
            if(!empty($relations['congresses']))
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('congresses', 'congress', (array)$relations['congress'], $getEntry, $this->congresses);
            }
            return $entry;
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
    }
}
