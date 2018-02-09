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
use craft\fields\Entries as BaseField;

Class JSCImportService extends component
{
    private $sectionId,
        $entryType,
        $authorId,
        $typeId,
        $sectionTitle = '';
        $JSCObjects = [];

    public function __construct()
    {
        $this->JSCObjects = $this->getJSC($this->sectionTitle);
    }

    /*
     * Get all studies, put it into an 
     * array with the titles as keys
     */
    public function getJSCList()
    {
        if(!empty($this->JSCObjects))
        {
            $studies = $this->JSCObjects;
        } else {
            $studies = $this->getAllStudies();
        }

        $JSCList = [];
        foreach($JSCList as $list)
        {
            $JSCList[$list>title]['title'] = $list>title;
            $JSCList[$lsit>title]['id'] = $list>id;
        }
        return $JSCList;
    }

    /*
     * Get all the studies from Craft
     */
    public function getJSC(string $sectionTitle)
    {        
        $query = Entry::find()
            ->section($sectionTitle)
            ->all();

        // We just need 1 entry as a base to
        // grab the information we need.
        //
        // To save anything, craft depends on this
        // so if you're saving in Entries, makes
        // sure the getJSCList is initiated first
        $this->sectionId = $query[0]->sectionId;
        $this->entryType = $query[0]->type;
        $this->authorId = $currentUser = Craft::$app->getUser()->getIdentity()->id;

        // Change the keys to title for
        // easy searching!
        $studyCleaned = [];
        foreach($query as $craftData)
        {
            $dataCleaned[$craftData->title] = $craftData;
        }

        return $dataCleaned; 
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
    public function getJSCField(Entry $craftEntry)
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
    public function importArrayToEntries(string $sectionTitle, array $jscEntries)
    {
        $this->sectionTitle = $sectionTitle;

        // Get list of studies already in the system
        $jscEntries = $this->getJSCList();

        // Check if there's any changes, if not add new entry
        foreach($jscEntries as $entry)
        {
            if(isset($this->JSCObjects[$entry['title']]))
            {
                $this->prepareAndSave($entry, $this->JSCObjects[$entry['title']]);
            } else {
                $this->saveNewJSC('', $entry, true);
                Triton::getInstance()->entryChangeService->addNewEntry($entry['title']);
            }

            // delete from array so that we're
            // left with publications that have been
            // deleted.
            unset($this->JSCObjects[$entry['title']]);
        }

        // If anything is left in the array then we
        // need to delete(disable) these records
        if(count($this->JSCObjects) > 0)
        {
            foreach($this->JSCObjects as $deletedEntry)
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
    public function saveJSCRelation(string $sectionTitle, array $jsc, Entry &$craftEntry)
    {
        $this->sectionTitle = $sectionTitle;
        $jscField = $this->getJSCField($craftEntry);

        $jscIds = [];
        foreach($jsc as $entry)
        {
            if(isset($this->JSCObjects[$entry]))
            {
                $entryIds[] = $this->JSCObjects[$entry]->id;
            } else {
                // Save a the study as a new entry
                //
                // TODO
                // This may need a 2nd look, in theory
                // the return should be giving
                $entryIds[] = $this->saveNewJSC($entry);
            }
        }

        $saveRelation = Craft::$app->relations->saveRelations($jscField, $craftEntry, $entryIds);
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
    public function prepareAndSave(array $data, Entry $craftData)
    {
        // Get list of study headers
        $headers = $this->getHeaderFields();
        
        // Track changes
        $changed = 0;
        foreach($headers as $header)
        {
            // Check if it's a date time class
            // and do the necessary comparison
            if(is_a($craftData[$header], 'DateTime')) 
            {
                $data = new \DateTime($data[$header]);
                $data = $date->getTimestamp();
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
        }

        $craftData->title = $data['title'];
        unset($data['title']);

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($data);

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

            $newJSC->title = $jscData['title'];
            $newJSC->slug = str_replace(' ', '-', $jscData['title']);

            unset($jscData['title']);
            unset($jscData['slug']);

            $newJSC->setFieldValues($jscData);

            if($saveResult = Craft::$app->elements->saveElement($newJSC)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newJSC->getErrors(), true));
            }
        } else {
            $newJSC = new Entry();
            
            $newJSC->sectionId = $this->sectionId;
            $newJSC->typeId = $this->entryType->id;

            $newJSC->title = $jscTitle;

            // Not sure why DV gives out their fields with
            // a random space in the document titles
            //
            // TODO
            // Remove the space for slugs
            $newJSC->slug = str_replace(' ', '-', $jscTitle);

            if($saveResult = Craft::$app->elements->saveElement($newJSC)) {
                return $saveResult;
            } else {
                throw new \Exception("Saving failed: " . print_r($newJSC->getErrors(), true));
            }
        }
    }

    /*
     *  Setup array fields,
     *  probably need to put into a 
     *  Global array
     */
    protected function getHeaderFields()
    {
        $headerFields = [
            'title',
            'sacDate',
            'studyTitle'    
        ];
        return $headerFields;
    }
}


