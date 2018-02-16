<?php
/**
 * Triton plugin for Craft CMS 3.x
 *
 * JSON export for Neptune
 *
 * @link      www.fishawack.com
 * @copyright Copyright (c) 2018 GeeHim Siu
 */

namespace fishawack\triton\controllers;

use fishawack\triton\Triton;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;

ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    GeeHim Siu
 * @package   Triton
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/triton/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the DefaultController actionIndex() method';

        return $result;
    }

    /**
     *  Grab all the publications from DB and lay the json
     *  data correctly
     *
     * @return json
     */
    public function actionGetAllPublications()
    {
        $jsonArray = [];

        $queryAll = Entry::find()
            ->section('publications')
            ->all();

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
 
            $jsonArray[] = [
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

        //return $this->asJson($queryAll);
        return $this->asJson($jsonArray);
    }

    /*
     * Get all journals from Db and sort json data
     *
     * @return json
     */
    public function actionGetAllJournals()
    {
        $jsonArray = [];

        // Get all journals
        $queryAll = Entry::find()
            ->section('journals')
            ->all();        

        foreach($queryAll as $journal)
        {
            $jsonArray[] = [
                'id' => $journal->id,
                'title' => $journal->title
            ];
        }

        return $this->asJson($jsonArray);
    }

    /*
     *  Get all congresses and sort all data for Json
     *
     *  @return json
     */
    public function actionGetAllCongresses()
    {
        $jsonArray = [];

        // Get all journals
        $queryAll = Entry::find()
            ->section('congresses')
            ->all();        

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

            $jsonArray[] = [
                'id' => $journal->id,
                'title' => $journal->title,
                'acronym' => $journal->congressAcronym,
                'dueDate' => $dueDate,
                'fromDate' => $fromDate,
                'toDate' => $toDate
            ];
        }

        return $this->asJson($jsonArray);
    }

    /*
     *
     */
    public function actionGetAllStudies()
    {
        $jsonArray = [];

        // Get all journals
        $queryAll = Entry::find()
            ->section('studies')
            ->all();        

        foreach($queryAll as $studies)
        {
            $sacDate = '';
            if($studies->sacDate)
            {
                $sacDate = $studies->sacDate->format('d-m-Y'); 
            }
            $jsonArray[] = [
                'id' => $studies->id,
                'title' => $studies->title,
                'sacDate' => $sacDate,
                'studyTitle' => $studies->studyTitle
            ];
        }

        return $this->asJson($jsonArray);
    }

    /*
     *
     */
    public function actionDynamic()
    {
        // Get all journals
        $queryAll = Entry::find()
            ->section('studies')
            ->fields();        

        return $this->asJson($queryAll);
    }

    /*
     *  testing!
     *  
     * @return json
     */
    public function actionGetPubTitle()
    {
        $jsonArray = [];

        // Get all journals
        $queryAll = Entry::find()
            ->section('publications')
            ->type();        

        foreach($queryAll as $journal)
        {
            $jsonArray[] = [
                'id' => $journal->id,
                'title' => $journal->title
            ];
        }

        return $this->asJson($jsonArray);
    }    

    /*
     * Update our generated Json cache,
     * you can always use the live links
     * but the speed isn't instant
     *
     */
    public function actionUpdateJsonCache()
    {
        $result = Triton::getInstance()->jsonService->updateAllJsonCache();
        return $this->renderTemplate('triton/notifications', [
           'results' => $results 
        ]);
    }
}
