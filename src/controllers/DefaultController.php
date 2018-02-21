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

    /**
     *  Grab all the publications from DB and lay the json
     *  data correctly
     *
     * @return json
     */
    public function actionGetAllPublications($json = true)
    {
        Craft::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Origin', '*');
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('publications');

        // Get our json structure
        $jsonStructure = Triton::getInstance()->variablesService->getPublicationJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure);
        
        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all journals from Db and sort json data
     *
     * @return json
     */
    public function actionGetAllJournals($json = true)
    {
        // Get all journals
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('journals');
        $jsonStructure = Triton::getInstance()->variablesService->getJournalsJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure);

        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     *  Get all congresses and sort all data for Json
     *
     *  @return json
     */
    public function actionGetAllCongresses($json = true)
    {
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('congresses');
        $jsonStructure = Triton::getInstance()->variablesService->getCongressJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure);

        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all studies
     *
     * @return json
     */
    public function actionGetAllStudies($json = true)
    {
        // Get all studies
        $queryAll = Triton::getInstance()->jscImportService->getAllEntriesUntouched('studies');

        $jsonStructure = Triton::getInstance()->variablesService->getStudiesJsonStruc();
        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure);

        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all tags
     * 
     * @return json
     */
    public function actionGetAllTags($json = true)
    {
        // Get all Tags
        $queryAll = Triton::getInstance()->jscImportService->getAllCategoriesUntouched('publicationTags');
        $jsonStructure = Triton::getInstance()->variablesService->getTagsJsonStruc();
        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure);

        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all globals
     *
     * @return json
     */
    public function actionGetAllGlobals($json = true)
    {
        // Add more globals as the application grows,
        // current we only have the Datavision import
        // date.
        //
        // Wrap our result in an array and set single
        // to true
        $queryDVDate = array(Triton::getInstance()->queryService->queryOneGlobalSet('datavisionExportDate'));
        $jsonStructure = Triton::getInstance()->variablesService->getGlobalsJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryDVDate, $jsonStructure, true);
        
        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all globals
     *
     * @return json
     */
    public function actionGetAllDoctypes($json = true)
    {
        $queryAll = Triton::getInstance()->queryService->queryAllCategories('DocumentType');

        $jsonStructure = Triton::getInstance()->variablesService->getCategoryJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure, false, true);
        
        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Get all globals
     *
     * @return json
     */
    public function actionGetAllCategories($json = true)
    {
        $queryAll = Triton::getInstance()->queryService->queryAllCategories('keyAreasOfKnowledge');

        $jsonStructure = Triton::getInstance()->variablesService->getCategoryJsonStruc();

        $results = Triton::getInstance()->jsonService->getSectionDataFormatted($queryAll, $jsonStructure, false, true);
        
        if($json)
        {
            return $this->asJson($results);
        }

        return $results;
    }

    /*
     * Update our generated Json cache,
     * you can always use the live links
     * but the speed isn't instant.
     *
     * We'll need to segregate it for better
     * debugging capabilities and also we can 
     * then do some cute little ajax update
     * for the front end
     *
     */
    public function actionUpdateJsonCache()
    {
        $data = $_REQUEST['data'];

        // Get the function corresponding to
        // the data being requested
        $dataFunctionName = Triton::getInstance()->jsonService->findFunctionForData($data);

        if(!$dataFunctionName)
        {
            $result['error'] = "No section with this function!";
            return $this->asJson($result);
        }

        // Set json to false for the arrays
        // to be brought back
        $function = $this->$dataFunctionName(false);

        $result = Triton::getInstance()->jsonService->updateJsonFile($function, $data);
        return $this->asJson($result);
    }
}
