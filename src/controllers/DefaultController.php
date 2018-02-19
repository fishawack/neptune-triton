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
    public function actionGetAllPublications()
    {
        //return $this->asJson($queryAll);
        return $this->asJson(Triton::getInstance()->jsonService->getAllPublications());
    }

    /*
     * Get all journals from Db and sort json data
     *
     * @return json
     */
    public function actionGetAllJournals()
    {
        return $this->asJson(Triton::getInstance()->jsonService->getAllJournals());
    }

    /*
     *  Get all congresses and sort all data for Json
     *
     *  @return json
     */
    public function actionGetAllCongresses()
    {
        return $this->asJson(Triton::getInstance()->jsonService->getAllCongresses());
    }

    /*
     * Get all studies
     *
     * @return json
     */
    public function actionGetAllStudies()
    {
        return $this->asJson(Triton::getInstance()->jsonService->getAllCongresses());
    }

    /*
     * Get all tags
     * 
     * @return json
     */
    public function actionGetAllTags()
    {
        return $this->asJson(Triton::getInstance()->jsonService->getAllTags());
    }

    /*
     * Get all globals
     *
     * @return json
     */
    public function actionGetAllGlobals()
    {
        return $this->asJson(Triton::getInstance()->jsonService->getAllGlobals());
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
        $result = Triton::getInstance()->jsonService->updateJsonFile($data);
        return $this->asJson($result);
    }
}
