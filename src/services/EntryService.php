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

//ini_set('xdebug.var_display_max_depth', 1000);

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
        $product,
        $authorId,
        $studies,
        $journals,
        $congresses;

    /*
     *  Excel to CSV
     *
     *  Uses special format of excel from ES!
     *  Make sure you're importing the right spreadsheet!
     */
    public function excelToCsv($csvData, $product) {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $entryExample = Triton::getInstance()->queryService->queryOneEntry('publications');

        $this->sectionId = $entryExample->sectionId;
        $this->entryType = $entryExample->type;
        $this->authorId = $currentUser->id; 


        $import = [];
        foreach($csvData as $index => $data) {
            $this->product = $product;
            $delimit = explode('`', $data);

            $study = [];
            if(isset($delimit[14]))
            {
                $studyArray = explode("  ", str_replace('"','', $delimit[14]));
                foreach($studyArray as $index => $studyItem) {
                    $trim = trim($studyItem);
                    if($trim !== '')
                    {
                        $study[$index] = trim($studyItem);
                    }
                }
            }

            $tags = [];
            if(isset($delimit[19]))
            {
                $tags = explode("  ", trim($this->clearQuotes($delimit[19])));
                $availableTags = Triton::getInstance()->jscImportService->checkCategoryItems('publicationTags', $tags);

                $tagRelations = [];
                foreach($tags as $item) 
                {
                    $item = trim($item);
                    if(isset($availableTags[$item])) {
                        $tagRelations[] = (int)$availableTags[$item];
                    } else {
                        if($item !== '')
                        {
                            $tagRelations[] = Triton::getInstance()->jscImportService->saveNewCategoryEntry('publicationTags', $item);
                        }
                    }
                }
            }

            // We only have one column for this table
            $download = [];
            if(isset($delimit[20]))
            {
                $files = explode('  ', $delimit[20]);
                foreach($files as $file) 
                {
                    $download[] = [
                        'col1' => $this->clearQuotes($file)
                    ];
                }
            }

            // Check to see if we have the special section!
            $docTypeData = array(trim($this->clearQuotes($delimit[8])));
            $availableDocType = Triton::getInstance()->jscImportService->checkCategoryItems('documentType', $docTypeData);
            $docType = [];
            if(isset($availableDocType[$docTypeData[0]])) {
                $docType[] = (int)$availableDocType[$docTypeData[0]];
            }

            if($delimit[1] !== '') 
            {
                $product = trim($this->clearQuotes($delimit[1]));
                $this->product = $product; 
            }

            // Check to see if we have the special section!
            $special = array(trim($this->clearQuotes($delimit[18])));
            $availableCats = Triton::getInstance()->jscImportService->checkCategoryItems('keyAreasOfKnowledge', $special);
            $category = [];

            // Had a spreadhseet that somehow contained double spaces in the entry,
            // craft will remove double spaces therefore it's not recognised
            if(!isset($availableCats[$special[0]])) {
                $category[] = Triton::getInstance()->jscImportService->saveNewCategoryEntry('keyAreasOfKnowledge', $this->clearQuotes($special[0]));
            } else {
                $category[] = (int)$availableCats[$special[0]];
            }

            if($delimit[1] !== '') 
            {
                $product = trim($this->clearQuotes($delimit[1]));
                $this->product = $product; 
            }

            $entry = [
                'title' => $this->clearQuotes($delimit[0]),
                'product' => $product,
                'documentTitle' => $this->clearQuotes($delimit[2]),
                'documentStatus' => array($this->clearQuotes($delimit[3])),
                'startDate' => date('Y-m-d H:i:s', strtotime($delimit[4])),
                'submissionDate' => $this->clearQuotes($delimit[5]),
                'documentAuthor' => $this->clearQuotes($delimit[6]),
                'documentType' => $this->clearQuotes($delimit[7]),
                'docType' => $docType,
                'journal'=> $this->clearQuotes($delimit[9]),
                'congress'=> trim($this->clearQuotes($delimit[10])),
                'citation'=> $this->clearQuotes($delimit[11]),
                'citationUrl' => $this->clearQuotes($delimit[12]),
                'publicationDate' => date('Y-m-d H:i:s', strtotime($delimit[13])),
                'relatedPubs' => array($delimit[16]),
                'summary'=> $this->clearQuotes($delimit[17]),
                'study' => $study,
                'category' => array($this->clearQuotes($delimit[8])),
                'keyAreasOfKnowledge'=> $category,
                'category' => $category,
                'publicationTags' => $tagRelations,
                'file' => $download,
                'lock'=> true
            ];

            /*
             * Check if entry is already available, if so check to see what's changed,
             * v1.0.1 we will keep this ignored for now
             *
             * TODO
             */
            if(Triton::getInstance()->queryService->queryEntryByTitle($entry['title'])) 
            {
                Triton::getInstance()->entryChangeService->addIgnoredEntry($entry['title']);
            } else {
                $this->newEntry($entry);
                Triton::getInstance()->entryChangeService->addNewEntry($entry['title']);
            }
        }

        return Triton::getInstance()->entryChangeService->getStatus();
    }

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
     *  @param string $product
     */
    public function importArrayToEntries(array $data, string $product = '')
    {
        // Set our product
        $this->product = $product;
        // Set the import date!
        $DVDate = Triton::getInstance()->queryService->queryOneGlobalSet('datavisionExportDate');

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
        $allPublications = Triton::getInstance()->queryService->getAllEntriesUntouched('publications');
        $allPublications = Triton::getInstance()->queryService->swapKeys($allPublications);

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

        // Get list of items to disregard
        $disregards = Triton::getInstance()->variablesService->getDisregards();

        // Check if there's any changes, if not add new entry
        foreach($data as $entry)
        {
            // check if there's any special characters
            // that didn't pull through
            if(strpos($entry['documentAuthor'], '?') !== false) {
                Triton::getInstance()->entryChangeService->addErrorEntry($entry['title']);
            }            

            $bypass = false;
            // TODO 
            // Make this part more elegant, we cannot
            // continue adding random conditional
            // events 
            foreach($disregards as $ignore)
            {
                if(isset($entry[$ignore['handle']]) && in_array($entry[$ignore['handle']], $ignore['ignore']))
                {
                    $bypass = true;
                    Triton::getInstance()->entryChangeService->addIgnoredEntry($entry['title']);
                }
            }

            if(!$bypass)
            {
                // check if there's any special characters
                // that didn't pull through
                if(strpos($entry['documentAuthor'], '?') !== false) {
                    Triton::getInstance()->entryChangeService->addErrorEntry($entry['title']);
                }

                /*
                 * All objects were saved into an array so we could access the key however
                 * there seems to be a problem with certain entries, sadly we will have to revert back
                 * to using the full query!
                 */
                $lookup = Triton::getInstance()->queryService->queryEntryByTitle($entry['title']);

                if(isset($lookup->title)) 
                {
                    // Check if the record is locked
                    if(isset($allPublications[$entry['title']]->lock) && $allPublications[$entry['title']]->lock === '1')
                    {
                        Triton::getInstance()->entryChangeService->addLocked($entry['title']);
                    } else {
                        $this->saveExisting($entry, $lookup);
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
        }

        // If anything is left in the array then we
        // need to delete(disable) these records
        if(count($allPublications) > 0)
        {
            foreach($allPublications as $remainingEntries)
            {
                // change lock to boolean
                $lock = (bool)$remainingEntries->lock;
                if(!$remainingEntries->lock)
                {
                    // Some records are entered in
                    // manually therefore we need
                    // to check to make sure these
                    // aren't locked before deleting
                    $this->deleteEntry($remainingEntries);
                } else {
                    Triton::getInstance()->entryChangeService->addLocked($remainingEntries['title']);
                }
            }
        }

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
            // Make sure titles are trimeed!
            $publication->title = trim($publication->title);

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
            } elseif(is_a($craftData->$data, 'craft\elements\db\CategoryQuery')) {
                $fieldValue = '';
                /*
                 * Since some fields can have multiple values,
                 * we need to loop through and find out if any
                 * are the same
                 */
                foreach($craftData->$data as $fieldData)
                {
                    $fieldValue = $fieldData->title;
                }

                /* 
                 * if our item is a Craft Category object 
                 * then we need to sort it in a different way
                 */
                //if((string)$craftData->$data->title !== (string)$csvData[$data])
                if($fieldValue !== (string)$csvData[$data])
                {
                    // Get Category group
                    $categoryGroupId = $craftData->$data->groupId[0];
                    // Grab the id needed for our category type
                    $category = Triton::getInstance()->queryService->queryCategoryById($categoryGroupId);

                    Triton::getInstance()->jscImportService->saveCategoryRelation($data, (array)$csvData['docType'], $craftData);
                    Triton::getInstance()->entryChangeService->addChanged($craftData->title, $data);
                    $changed++;
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

        $csvData['documentStatus'] = $this->cleanPublishedStatus($csvData['documentStatus']);

        /**
         *  Save everything else as normal!
         */
        $craftData->setFieldValues($csvData);

        $status = Triton::getInstance()->entryChangeService->getStatus();

        if(Craft::$app->elements->saveElement($craftData)) {
            return true;
        } else {
            throw new \Exception($csvData['documentTitle']." Saving failed: " . print_r($craftData->getErrors(), true));
        }
    }

    public function appendData(array $data)
    {
        $record = Triton::getInstance()->queryService->queryEntryByTitle($data['title'], 'publications');
        if(!$record) {
            Triton::getInstance()->entryChangeService->addErrorEntry($data['title']);
            return false;
        }

        $tags = $data['publicationTags'];
        $areas = $data['category'];
        $product = array($data['product']);
        $congress = $data['congress'];

        unset($data['product']);
        unset($data['title']);
        unset($data['congress']);

        if($areas)
        {
            $availableAreas = Triton::getInstance()->jscImportService->checkCategoryItems('keyAreasOfKnowledge', $areas);
            $areaRelations = [];
            foreach($areas as $area) 
            {
                $area = trim($area);
                if(isset($availableAreas[$area]))
                {
                    $areaRelations[] = (int)$availableAreas[$area];
                } else {
                    if($area !== '')
                    {
                        $areaRelations[] = Triton::getInstance()->jscImportService->saveNewCategoryEntry('keyAreasOfKnowledge', $area);
                    }
                }
            }

            $data['category'] = $areaRelations;
        }

        if($tags)
        {
            $availableTags = Triton::getInstance()->jscImportService->checkCategoryItems('publicationTags', $tags);
            $tagRelations = [];
            foreach($tags as $item) 
            {
                $item = trim($item);
                if(isset($availableTags[$item])) {
                    $tagRelations[] = (int)$availableTags[$item];
                } else {
                    if($item !== '')
                    {
                        $tagRelations[] = Triton::getInstance()->jscImportService->saveNewCategoryEntry('publicationTags', $item);
                    }
                }
            }

            $data['publicationTags'] = $tagRelations;
        }

        // Clear all Objectives
        // Bayer specific, to be remove
        $data['objectives'] = '';

        $record->setFieldValues($data);

        if(Craft::$app->elements->saveElement($record)) {
            Triton::getInstance()->jscImportService->saveJSCRelation('products', 'product', $product, $record);

            if(isset($congress) && strlen($congress) > 0)
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('congresses', 'congress', (array)$congress, $record, [], true);
            }
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($craftData->getErrors(), true));
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
     * ---
     *
     * This has been reworked so that we
     * show a list of what has been removed or doesn't
     * exist anymore however the entry will still be 
     * enabled, it shouldn't be used by anything
     *
     */
    public function deleteEntry(Entry $craftEntry)
    {
        // All custom fields in Craft requires you to run a foreach
        // to get the value 
        $associatedProduct = '';
        foreach($craftEntry->product as $product)
        {
            $associatedProduct = (string)$product->title;
        }

        // Check if entry and product is what we're dealing with
        if($associatedProduct === $this->product)
        {
            Triton::getInstance()->entryChangeService->addDeletedEntry((string)$craftEntry->title);

            // Disable the entry
            $craftEntry->enabled = 0;

            if(Craft::$app->elements->saveElement($craftEntry)) {
                return true;
            } else {
                throw new \Exception("Saving failed: " . print_r($craftEntry->getErrors(), true));
            }
        }
    }

    /*
     * Append data to publications
     */
    public function appendDataToPubs(array $data, string $product)
    {
        // clear first two columns
        unset($data[0]);
        unset($data[1]);
        $csvStructure = Triton::getInstance()->variablesService->getAppendDataStructure();

        foreach($data as $pub)
        {
            $newData = [];
            $pub = explode('`', $pub);

            foreach($pub as $key => $value)
            {
                $key = $csvStructure[$key];
                $value = str_replace("\n", '', $value);
                $value = str_replace('"', '', $value);
                $newData[$key] = $value;

                
                // If we're dealing with files
                if($key === 'file') 
                {
                    $files = explode('  ', $value);
                    $newData['file'] = [];
                    foreach($files as $file)
                    {
                        $newData['file'][] = [
                            'col1' => $file
                        ];
                    }
                }

                if($key === 'publicationTags') 
                {
                    $tags = [];
                    $allTags = explode('  ', $value);
                    foreach($allTags as $tag)
                    {
                        $tags[] = trim($tag);
                    }

                    $newData['publicationTags'] = $tags;
                }

                if($key === 'category') 
                {
                    $areas = [];
                    $allAreas = explode('  ', $value);
                    foreach($allAreas as $area)
                    {
                        $areas[] = trim($area);
                    }

                    $newData['category'] = $areas;
                }

            }
            $this->appendData($newData);
        }

        die();
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
        //$entry->product = $this->product;
        $entry->slug = str_replace(' ', '-', $csvData['title']);

        // Setup relations to be imported
        $relations = [];
        $relations['studies'] = $csvData['study'];

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

        if(isset($csvData['tags']))
        {
            $relations['tags'] = $csvData['tags'];
            unset($csvData['tags']);
        }

        if(isset($csvData['keyAreasOfKnowledge']))
        {
            $relations['keyAreasOfKnowledge'] = $csvData['keyAreasOfKnowledge'];
            unset($csvData['keyAreasOfKnowledge']);
        }

        if(isset($csvData['docType']))
        {
            $array = (array)$csvData['docType'];
            $availableTypes = Triton::getInstance()->jscImportService->checkCategoryItems('documentType', $array);
            $typesRelations = [];
            foreach($array as $item) 
            {
                $item = trim($item);
                if(isset($availableTypes[$item])) {
                    $typesRelations[] = (int)$availableTypes[$item];
                } else {
                    if($item !== '')
                    {
                        $typesRelations[] = Triton::getInstance()->jscImportService->saveNewCategoryEntry('documentType', $item);
                    }
                }
            }

            $csvData['docType'] = $typesRelations;
        }
        // New way of seting fields, need to remove our title
        // for set fields to work
        unset($csvData['title']);
        unset($csvData['study']);

        $entry->setFieldValues($csvData);

        if($savedEntry = Craft::$app->elements->saveElement($entry)) {
            // Save Relationship with our other sections
            $getEntry = Triton::getInstance()->queryService->queryEntryByTitle($entry->title);

            // Save the product field
            Triton::getInstance()->jscImportService->saveJSCRelation('products', 'product', (array)$this->product, $getEntry);
            if(!empty($relations['studies']))
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('studies', 'study', $relations['studies'], $getEntry, $this->studies, true);
            }

            if(!empty($relations['journal']))
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('journals', 'journal', (array)$relations['journal'], $getEntry, $this->journals);

            }

            if(!empty($relations['congress']))
            {
                Triton::getInstance()->jscImportService->saveJSCRelation('congresses', 'congress', (array)$relations['congress'], $getEntry, $this->congresses, true);
            }

            // Save our DocType which is a craft\Category
            // Get Category group
            $categoryGroupId = $getEntry->docType->groupId;
            // Grab the id needed for our category type
            $category = Triton::getInstance()->queryService->queryCategoryById($categoryGroupId);

            if(isset($csvData['docType']))
            {
                Triton::getInstance()->jscImportService->saveCategoryRelation('docType', (array)$csvData['docType'], $getEntry);
            }

            return $entry;
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
    }

    private function clearQuotes(string $string)
    {
        return str_replace('"', '', $string);
    }

    /**
     * Bug where status gets capitalised
     */
    private function cleanPublishedStatus($status) 
    {
        switch ($status) {
            case 'Epublished':
                $status = 'ePublished';
                break;
            default:
                break;
        }

        return $status;
    }
}
