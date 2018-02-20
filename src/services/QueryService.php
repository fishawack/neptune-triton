<?php

/**
 *
 * Query Service
 * =============
 *
 * Have the different queries here to be
 * used in other services
 */
namespace fishawack\triton\services;

use fishwack\triton\Triton;
use Craft;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\GlobalSet;
use yii\base\Component;

class QueryService extends Component
{
    public function queryAllGlobalSet()
    {
    }

    /*
     *  Get one result from globalsets
     *
     *  @param string $sectionHandle
     */
    public function queryOneGlobalSet(string $sectionHandle)
    {
        $globalset = GlobalSet::find()
            ->handle($sectionHandle)
            ->one();

        return $globalset;
    }

    /*
     * @param string $sectionHandle
     */
    public function queryAllEntries(string $sectionHandle)
    {
        $entries = Entry::find()
            ->section($sectionHandle)
            ->all();

        return $entries;
    }

    /*
     * Get one entry from selected section
     *
     * @param string $sectionHandle
     */
    public function queryOneEntry(string $sectionHandle)
    {
        $entries = Entry::find()
            ->section($sectionHandle)
            ->one();

        return $entries;
    }

    /*
     * @param string $sectionHandle
     */
    public function queryAllCategories(string $categoryTitle)
    {
        // Find the correct group id
        $group = Craft::$app->getCategories()->getGroupByHandle($categoryTitle);
        $query = Category::find()
            ->groupId($group->id)
            ->all();

        return $query;
    }

    /*
     *  Swap keys so that we can sift 
     *  through out array and do minupulation
     *
     *  @param array $originalData
     */
    public function swapKeys(array $originalData)
    {
        $dataCleaned = [];
        foreach($originalData as $newData)
        {
            $dataCleaned[$newData->title] = $newData;
        }

        return $dataCleaned;
    }
}
