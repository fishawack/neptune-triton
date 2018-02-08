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

    public function createNewStudy()
    {
        return true;
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
                // TODO
                // Save a the study as a new entry
            }
        }

        $saveRelation = Craft::$app->relations->saveRelations($studyField, $craftEntry, $studyIds);
        return $saveRelation;
    }

    public function saveNewStudy()
    {

    }
    
    protected function addToStudyList($studyTitle, $studyId)
    {
        $this->studies[$studyTitle]['title'] = $studyTitle;
        $this->studies[$studyTitle]['id'] = $studyId;
    }
}

