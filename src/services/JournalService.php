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

Class JournalService extends component
{
    private $sectionId,
        $entryType,
        $authorId,
        $typeId,
        $studies = [];

    public function __construct()
    {
        $this->studies = $this->getAllJournals();
    }

    /*
     * Get all studies, put it into an 
     * array with the titles as keys
     */
    public function getStudyList()
    {
        if(!empty($this->studies))
        {
            $studies = $this->studies;
        } else {
            $studies = $this->getAllJournals();
        }

        $journalList = [];
        foreach($studies as $journal)
        {
            $journalList[$journal->title]['title'] = $journal->title;
            $journalList[$journal->title]['id'] = $journal->id;
        }
        return $journalList;
    }

    /*
     * Get all the studies from Craft
     */
    public function getAllJournals()
    {        
        $queryJournals = Entry::find()
            ->section('studies')
            ->all();

        // We just need 1 entry as a base to
        // grab the information we need.
        //
        // To save anything, craft depends on this
        // so if you're saving in Entries, makes
        // sure the getStudyList is initiated first
        $this->sectionId = $queryJournals[0]->sectionId;
        $this->entryType = $queryJournals[0]->type;
        $this->authorId = $currentUser = Craft::$app->getUser()->getIdentity()->id;

        // Change the keys to title for
        // easy searching!
        $journalCleaned = [];
        foreach($queryJournals as $journal)
        {
            $journalCleaned[$journal->title] = $journal;
        }

        return $journalCleaned; 
    }

    /*
     *  Grab the stufy by title,
     *  runs a search via Craft
     *
     *  @param string $journalTitle
     */
    public function getStudyByTitle(string $journalTitle)
    {
        $queryJournals = Entry::find()
            ->section('studies')
            ->title($journalTitle)
            ->one();

        return $queryJournals;         
    }

    /**
     *  Get the journal field
     *
     *  @param Entry $craftEntry
     */
    public function getStudyField(Entry $craftEntry)
    {
        $fields = $craftEntry->getFieldLayout()->getFields();

        $journalField = 0;
        foreach($fields as $field)
        {   
            if($field->handle == 'journal')
            {
                $journalField = $field;
            }
        }

        return $journalField;
    }

    /**
     *  CSV Import
     *
     *  Get the cleaned and exploded array
     *
     *  @param array $studies
     */
    public function importArrayToEntries(array $studies)
    {
        // Get list of studies already in the system
        $journalList = $this->getStudyList();
        $journalHeader = $this->getStudyArrayFields();

        // Check if there's any changes, if not add new entry
        foreach($studies as $entry)
        {
            if(isset($this->studies[$entry['title']]))
            {
                $this->prepareAndSaveStudy($entry, $this->studies[$entry['title']]);
            } else {
                $this->saveNewStudy('', $entry, true);
                Triton::getInstance()->entryChangeService->addNewEntry($entry['title']);
            }

            // delete from array so that we're
            // left with publications that have been
            // deleted.
            unset($this->studies[$entry['title']]);
        }

        // If anything is left in the array then we
        // need to delete(disable) these records
        if(count($this->studies) > 0)
        {
            foreach($this->studies as $deletedEntry)
            {
                Triton::getInstance()->entryService->deleteEntry($deletedEntry);
            }
        }

        return Triton::getInstance()->entryChangeService->getStatus();
    }

    /**
     *  Save studies that are already on the 
     *  system
     *
     *  @param array $studies
     *  @param Entry $craftEntry
     */
    public function saveStudyRelation(array $studies, Entry &$craftEntry)
    {
        //$journalField = new BaseField();
        //$journalField->id = $this->getStudyField($craftEntry);

        $journalField = $this->getStudyField($craftEntry);

        $journalIds = [];
        foreach($studies as $journal)
        {
            if(isset($this->studies[$journal]))
            {
                $journalIds[] = $this->studies[$journal]->id;
            } else {
                // Save a the journal as a new entry
                //
                // TODO
                // This may need a 2nd look, in theory
                // the return should be giving
                $journalIds[] = $this->saveNewStudy($journal);
            }
        }

        $saveRelation = Craft::$app->relations->saveRelations($journalField, $craftEntry, $journalIds);
        return $saveRelation;
    }

    /**
     * Save journal and make sure the comparisons 
     * are returned
     *
     * @param array $journalData
     * @param Entry $craftData
     *
     */
    public function prepareAndSaveStudy(array $journalData, Entry $craftData)
    {
        // Get list of journal headers
        $journalHeader = $this->getStudyArrayFields();
        
        // Track changes
        $changed = 0;
        foreach($journalHeader as $header)
        {
            // Check if it's a date time class
            // and do the necessary comparison
            if(is_a($craftData[$header], 'DateTime')) 
            {
                $journalDate = new \DateTime($journalData[$header]);
                $journalDate = $journalDate->getTimestamp();
                $craftTime = $craftData->$header->getTimestamp();
                
                // change CraftEntry datetime for comparison
                if($journalDate !== $craftTime)
                {
                    $changed++;
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            } else {
                if((string)$journalData[$header] !== (string)$craftData->$header)
                {
                    $changed++;
                    // Add change to the service for later use
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            }   
        }

        if($changed === 0)
        {
            Triton::getInstance()->entryChangeService->addUnchanged($journalData['title']);    
        }

        $craftData->title = $journalData['title'];
        unset($journalData['title']);

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($journalData);

        $status = Triton::getInstance()->entryChangeService->getStatus();

        if(Craft::$app->elements->saveElement($craftData)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($craftData>getErrors(), true));
        }
    }

    /**
     * Check if it's created via upload
     * csv or through publication import -
     * through Csv we need to build the 
     * relations
     *
     * @param string $journalData
     * @param bool $upload
     */
    public function saveNewStudy(string $journalTitle, array $journalData = [], bool $upload = false)
    {
        if($upload == true)
        {
            if(empty($journalData))
            {
                throw new \Exception("No values entered for studies");
            }

            $newStudy = new Entry();
 
            $newStudy->sectionId = $this->sectionId;
            $newStudy->typeId = $this->entryType->id;

            $newStudy->title = $journalData['title'];
            $newStudy->slug = str_replace(' ', '-', $journalData['title']);

            unset($journalData['title']);
            unset($journalData['slug']);

            $newStudy->setFieldValues($journalData);

            if($saveResult = Craft::$app->elements->saveElement($newStudy)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newStudy->getErrors(), true));
            }
        } else {
            $newStudy = new Entry();
            
            $newStudy->sectionId = $this->sectionId;
            $newStudy->typeId = $this->entryType->id;

            $newStudy->title = $journalTitle;

            // Not sure why DV gives out their fields with
            // a random space in the document titles
            //
            // TODO
            // Remove the space for slugs
            $newStudy->slug = str_replace(' ', '-', $journalTitle);

            if($saveResult = Craft::$app->elements->saveElement($newStudy)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newStudy->getErrors(), true));
            }
        }
    }

    /**
     * Unused action
     * -------------
     * Add studies to the list
     * @param string $journalTitle
     * @param string $journalId
     */
    protected function addToStudyList($journalTitle, $journalId)
    {
        $this->studies[$journalTitle]['title'] = $journalTitle;
        $this->studies[$journalTitle]['id'] = $journalId;
    }

    /*
     *  Setup array fields,
     *  probably need to put into a 
     *  Global array
     */
    protected function getStudyArrayFields()
    {
        $journalFields = [
            'title',
            'sacDate',
            'journalTitle'    
        ];
        return $journalFields;
    }
}


