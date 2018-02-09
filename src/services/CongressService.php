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

Class CongressService extends component
{
    private $sectionId,
        $entryType,
        $authorId,
        $typeId,
        $studies = [];

    public function __construct()
    {
        $this->studies = $this->getAllStudies();
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
            $studies = $this->getAllStudies();
        }

        $studyList = [];
        foreach($studies as $study)
        {
            $studyList[$study->title]['title'] = $study->title;
            $studyList[$study->title]['id'] = $study->id;
        }
        return $studyList;
    }

    /*
     * Get all the studies from Craft
     */
    public function getAllStudies()
    {        
        $queryStudies = Entry::find()
            ->section('studies')
            ->all();

        // We just need 1 entry as a base to
        // grab the information we need.
        //
        // To save anything, craft depends on this
        // so if you're saving in Entries, makes
        // sure the getStudyList is initiated first
        $this->sectionId = $queryStudies[0]->sectionId;
        $this->entryType = $queryStudies[0]->type;
        $this->authorId = $currentUser = Craft::$app->getUser()->getIdentity()->id;

        // Change the keys to title for
        // easy searching!
        $studyCleaned = [];
        foreach($queryStudies as $study)
        {
            $studyCleaned[$study->title] = $study;
        }

        return $studyCleaned; 
    }

    /*
     *  Grab the stufy by title,
     *  runs a search via Craft
     *
     *  @param string $studyTitle
     */
    public function getStudyByTitle(string $studyTitle)
    {
        $queryStudies = Entry::find()
            ->section('studies')
            ->title($studyTitle)
            ->one();

        return $queryStudies;         
    }

    /**
     *  Get the study field
     *
     *  @param Entry $craftEntry
     */
    public function getStudyField(Entry $craftEntry)
    {
        $fields = $craftEntry->getFieldLayout()->getFields();

        $studyField = 0;
        foreach($fields as $field)
        {   
            if($field->handle == 'study')
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
     *  @param array $studies
     */
    public function importArrayToEntries(array $studies)
    {
        // Get list of studies already in the system
        $studyList = $this->getStudyList();
        $studyHeader = $this->getStudyArrayFields();

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
        //$studyField = new BaseField();
        //$studyField->id = $this->getStudyField($craftEntry);

        $studyField = $this->getStudyField($craftEntry);

        $studyIds = [];
        foreach($studies as $study)
        {
            if(isset($this->studies[$study]))
            {
                $studyIds[] = $this->studies[$study]->id;
            } else {
                // Save a the study as a new entry
                //
                // TODO
                // This may need a 2nd look, in theory
                // the return should be giving
                $studyIds[] = $this->saveNewStudy($study);
            }
        }

        $saveRelation = Craft::$app->relations->saveRelations($studyField, $craftEntry, $studyIds);
        return $saveRelation;
    }

    /**
     * Save study and make sure the comparisons 
     * are returned
     *
     * @param array $studyData
     * @param Entry $craftData
     *
     */
    public function prepareAndSaveStudy(array $studyData, Entry $craftData)
    {
        // Get list of study headers
        $studyHeader = $this->getStudyArrayFields();
        
        // Track changes
        $changed = 0;
        foreach($studyHeader as $header)
        {
            // Check if it's a date time class
            // and do the necessary comparison
            if(is_a($craftData[$header], 'DateTime')) 
            {
                $studyDate = new \DateTime($studyData[$header]);
                $studyDate = $studyDate->getTimestamp();
                $craftTime = $craftData->$header->getTimestamp();
                
                // change CraftEntry datetime for comparison
                if($studyDate !== $craftTime)
                {
                    $changed++;
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            } else {
                if((string)$studyData[$header] !== (string)$craftData->$header)
                {
                    $changed++;
                    // Add change to the service for later use
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $header);
                }
            }   
        }

        if($changed === 0)
        {
            Triton::getInstance()->entryChangeService->addUnchanged($studyData['title']);    
        }

        $craftData->title = $studyData['title'];
        unset($studyData['title']);

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($studyData);

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
     * @param string $studyData
     * @param bool $upload
     */
    public function saveNewStudy(string $studyTitle, array $studyData = [], bool $upload = false)
    {
        if($upload == true)
        {
            if(empty($studyData))
            {
                throw new \Exception("No values entered for studies");
            }

            $newStudy = new Entry();
 
            $newStudy->sectionId = $this->sectionId;
            $newStudy->typeId = $this->entryType->id;

            $newStudy->title = $studyData['title'];
            $newStudy->slug = str_replace(' ', '-', $studyData['title']);

            unset($studyData['title']);
            unset($studyData['slug']);

            $newStudy->setFieldValues($studyData);

            if($saveResult = Craft::$app->elements->saveElement($newStudy)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newStudy->getErrors(), true));
            }
        } else {
            $newStudy = new Entry();
            
            $newStudy->sectionId = $this->sectionId;
            $newStudy->typeId = $this->entryType->id;

            $newStudy->title = $studyTitle;

            // Not sure why DV gives out their fields with
            // a random space in the document titles
            //
            // TODO
            // Remove the space for slugs
            $newStudy->slug = str_replace(' ', '-', $studyTitle);

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
     * @param string $studyTitle
     * @param string $studyId
     */
    protected function addToStudyList($studyTitle, $studyId)
    {
        $this->studies[$studyTitle]['title'] = $studyTitle;
        $this->studies[$studyTitle]['id'] = $studyId;
    }

    /*
     *  Setup array fields,
     *  probably need to put into a 
     *  Global array
     */
    protected function getStudyArrayFields()
    {
        $studyFields = [
            'title',
            'sacDate',
            'studyTitle'    
        ];
        return $studyFields;
    }
}


