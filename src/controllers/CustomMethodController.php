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
use craft\elements\Category;

/**
 * CustomMethodController
 * ==
 *
 * There are times when we need functions for one off actions, declare and use them here
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    GeeHim Siu
 * @package   Triton
 * @since     1.0.0
 */
class CustomMethodController extends Controller
{
    /**
     *  Grab all the publications from DB and lay the json
     *  data correctly
     *
     * @return json
     */
    public function actionConvertKogenate()
    {
        $category = Category::find()->slug('kogenate');
        $entries = Entry::find()
            ->section('publications')
            ->relatedTo($category)
            ->all();

        $items = [];
        foreach($entries as $entry)
        {
            //$attr = [
            //    'product' => array('Kogenate')
            //];

            //$newEntry = Craft::$app->elements->duplicateElement($entry, $attr);
            //$newId = $newEntry->id;

            //$updateEntry = Entry::find()->id($newId)->one();
            //$updateEntry->product = 'Kogenate';
            //if(!Craft::$app->elements->saveElement($updateEntry)) {
            //    throw new \Exception("Saving failed: " . print_r($updateEntry->getErrors(), true));
            //}
            var_dump($entry->title);
        }

        var_dump($items); die();
    }
}
