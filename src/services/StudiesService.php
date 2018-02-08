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

Class StudiesService extends component
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

    public function getStudyList()
    {
        $studies = $this->getAllStudies();

        $studyList = [];
        foreach($studies as $study)
        {
            $studyList[$study->title]['title'] = $study->title;
            $studyList[$study->title]['id'] = $study->id;
        }
        return $studyList;
    }


    /*
     *  Incomplete function
     */
    public function setupNewStudy($study)
    {
        $study = new Entry();

        $study->sectionId = $this->sectionId;
        $study->typeId = $this->entryType->id;

        $study->title = $study;
        $study->slug = str_replace(' ', '-', $study);

        return $study;
    }

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

    public function getStudyByTitle($studyTitle)
    {
        $queryStudies = Entry::find()
            ->section('studies')
            ->title($studyTitle)
            ->one();

        return $queryStudies;         
    }

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
     *  Save studies that are already on the 
     *  system
     *
     *  @param array $studies
     *  @param Entry $craftEntry
     */
    public function saveStudy($studies, &$craftEntry)
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
            // TODO
            //
            // importing and setting everything up
            // goes here 
            foreach($studyData as $study)
            {
                $newStudy = new Entry();
                
                $newStudy->sectionId = $this->sectionId;
                $newStudy->typeId = $this->entryType->id;

                $newStudy->title = $study['title'];
                $newStudy->slug = str_replace(' ', '-', $study['title']);

                unset($study['title']);

                $newStudy->setFieldValues($study);

                if($saveResult = Craft::$app->elements->saveElement($newStudy)) {
                } else {
                    throw new \Exception("Saving failed: " . print_r($newStudy->getErrors(), true));
                }
            }

            return $saveResult;

        } else {
            $newStudy = new Entry();
            
            $newStudy->sectionId = $this->sectionId;
            $newStudy->typeId = $this->entryType->id;

            $newStudy->title = $studyTitle;

            // Not sure why DV gives out their fields with
            // a random space in the document titles
            //
            // TODO
            // remove 2nd - from title 
            $newStudy->slug = str_replace(' ', '-', $studyTitle);

            if($saveResult = Craft::$app->elements->saveElement($newStudy)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newStudy->getErrors(), true));
            }
        }
    }
    
    protected function addToStudyList($studyTitle, $studyId)
    {
        $this->studies[$studyTitle]['title'] = $studyTitle;
        $this->studies[$studyTitle]['id'] = $studyId;
    }
}

