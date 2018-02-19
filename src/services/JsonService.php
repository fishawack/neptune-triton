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
    public function updateJsonFile($section)
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

        $data = '';
        switch($section)
        {
            case 'publications':
                $data = $this->getAllPublications();
                break;
            case 'studies':
                $data = $this->getAllStudies();
                break;
            case 'journals':
                $data = $this->getAllJournals();
                break;
            case 'congresses':
                $data = $this->getAllCongresses();
                break;
            case 'tags':
                $data = $this->getAllTags();
                break;
        }

        $data = json_encode($data);

        if(!$this->writeJsonFile($data, $fileData['path']))
        {   
            $result['error'] = "Something went wrong!";    
        }

        $result['complete'] = "'".$section."' cache has been successfully updated";
        return $result;
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
     *  Get all publication data
     *
     *  TODO refactor code to use variablesService
     */
    public function getAllPublications()
    {
        $dataArray = [];

        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('publications');

        foreach($queryAll as $query)
        {
            $id = '';

            if(isset($query->id))
            {
                $id = $query->id;
            }

            $title = '';
            if(isset($query->title))
            {
                $title = $query->title;
            }

            $citation = '';
            if(isset($query->citation))
            {
                $citation = $query->citation;
            }

            $citationUrl = '';
            if(isset($query->citationUrl))
            {
                $citationUrl = $query->citationUrl;
            }

            $status = '';
            if(isset($query->documentStatus))
            {   
                $status = $query->documentStatus->value;
            }

            $docNum = '';
            if(isset($query->title))
            {
                $docNum = $query->title;
            }

            $title = '';
            if(isset($query->documentTitle))
            {
                $title = $query->documentTitle;
            }

            $keyPub = false;
            if(isset($query->keyPublication))
            {
                $keyPub = $query->keyPublication;
            }

            $author = '';
            if(isset($query->documentAuthor))
            {
                $author = $query->documentAuthor;
            }

            $congresses = [];
            
            if(isset($query->congress))
            {
                foreach ($query->congress as $congress)
                {
                    $congresses[] = $congress->id;
                }
            }

            $journals = [];

            if(isset($query->journal))
            {
                foreach($query->journal as $journal)
                {
                    $journals[] = $journal->id;
                }
            }

            $studies = [];
            if(isset($query->study))
            {
                foreach($query->study as $study)
                {
                    $studies[] = $study->id;
                }
            }

            $categories = [];
            if(isset($query->category))
            {
                foreach($query->category as $category)
                {
                    $categories[] = $category->id;
                }
            }

            $related = [];
            if(isset($query->relatedPubs))
            {
                foreach($query->relatedPubs as $relatedEntry)
                {
                    $related[] = $relatedEntry>id;
                }
            }

            $pubTags = [];
            if(isset($query->publicationTags))
            {
                foreach($query->publicationTags as $tags)
                {
                    $pubTags[] = $tags->id;
                }
            }

            $docType = [];
            if(isset($query->docType))
            {
                foreach($query->docType as $type)
                {
                    $docType[] = $type->id;
                }
            }

            $publicationDate = '';
            if($query->publicationDate)
            {
                $publicationDate = $query->publicationDate->format('d-m-Y'); 
            }

            $startDate = '';
            if($query->startDate)
            {
                $startDate = $query->startDate->format('d-m-Y'); 
            }

            $submissionDate = '';
            if($query->submissionDate)
            {
                $submissionDate = $query->submissionDate->format('d-m-Y'); 
            }

            $summary = '';
            if(isset($query->summary))
            {
                $summary = $query->summary;
            }

            $objectives = '';
            if(isset($query->objectives))
            {
                $objectives = $query->objectives;
            }
 
            $dataArray[] = [
                'id' => $id,
                'docNum' => $title,
                'author' => $author,
                'citation' => $citation,
                'citationUrl' => $citationUrl,
                'congress' => $congresses,
                'journal' => $journals,
                'status' => $status,
                'title' => $title,
                'type' => $docType,
                'publicationDate' => $publicationDate,
                'startDate' => $startDate,
                'studies' => $studies,
                'submissionDate' => $submissionDate,
                'categories' => $categories,
                'related' => $related,
                'keyPublication' => $keyPub,
                'summary' => $summary,
                'objectives' => $objectives,
                'tags' => $pubTags
            ];
        }
        return $dataArray;
    }

    /*
     *  Get all study data
     *
     *  TODO refactor code to use variablesService
     */
    public function getAllJournals()
    {
        $dataArray = [];

        // Get all journals
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('journals');

        foreach($queryAll as $journal)
        {
            $dataArray[] = [
                'id' => $journal->id,
                'title' => $journal->title
            ];
        }

        return $dataArray;
    }

    /*
     *  Get all congress data
     *
     *  TODO refactor code to use variablesService
     */
    public function getAllCongresses()
    {
        $dataArray = [];

        // Get all journals
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('congresses');       

        foreach($queryAll as $journal)
        {
            $dueDate = '';
            if($journal->abstractDueDate)
            {
                $dueDate = $journal->abstractDueDate->format('d-m-Y');
            }

            $fromDate = '';
            if($journal->fromDate)
            {
                $fromDate = $journal->fromDate->format('d-m-Y');
            }

            $toDate = '';
            if($journal->toDate)
            {
                $toDate = $journal->toDate->format('d-m-Y');
            }

            $dataArray[] = [
                'id' => $journal->id,
                'title' => $journal->title,
                'acronym' => $journal->congressAcronym,
                'dueDate' => $dueDate,
                'fromDate' => $fromDate,
                'toDate' => $toDate
            ];
        }

        return $dataArray;
    }

    /*
     *  Get all study data
     *
     *  TODO refactor code to use variablesService
     */
    public function getAllStudies()
    {
        $dataArray = [];

        // Get all journals
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('studies');

        foreach($queryAll as $studies)
        {
            $sacDate = '';
            if($studies->sacDate)
            {
                $sacDate = $studies->sacDate->format('d-m-Y'); 
            }
            $dataArray[] = [
                'id' => $studies->id,
                'title' => $studies->title,
                'sacDate' => $sacDate,
                'studyTitle' => $studies->studyTitle
            ];
        }

        return $dataArray;
    }

    /*
     *
     */
    public function getAllTags()
    {
        // Stop duplicates
        $tags = [];
        $dataArray = [];

        // Get all journals
        $queryAll = Triton::getInstance()->jscImportService->getAllCategoriesUntouched('publicationTags');

        foreach($queryAll as $tag)
        {
            $dataArray[] = [
                'id' => $tag->id,
                'title' => $tag->title
            ];
        }

        return $dataArray;
    }
}
