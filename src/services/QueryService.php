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
    public function queryAllEntries(string $sectionHandle, $status = "live")
    {
        $entries = Entry::find()
            ->section($sectionHandle)
            ->status($status)
            ->all();

        for($i = 0; $i < count($entries); $i++)
        {
            $entries[$i]->title = trim($entries[$i]);
        }

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
     * Get one entry whether it's enabled or not
     *
     * @param string $entryId
     */
    public function queryEntryById($entryId)
    {
        $query = Entry::find()
            ->status(null)
            ->id($entryId)
            ->one();

        return $query;
    }

    /*
     * @param string $sectionHandle
     */
    public function queryAllCategories()
    {
        $query = Category::find()
            ->all();

        $results = $this->swapKeys($query);

        return $results;
    }

    public function queryCategoriesByTitle(string $categoryTitle)
    {
        // Find the correct group id
        $group = Craft::$app->getCategories()->getGroupByHandle($categoryTitle);
        if(!$group)
        {
            return false;
        }
        $query = Category::find()
            ->groupId($group->id)
            ->all();

        return $query;
    }

    public function queryCategoryById(int $categoryId)
    {
        // Find the correct group id
        $query = Category::find()
            ->groupId($categoryId)
            ->all();

        return $query;
    }

    /*
     * @param string $sectionHandle
     */
    public function queryOneCategory(string $categoryTitle)
    {
        // Find the correct group id
        $group = Craft::$app->getCategories()->getGroupByHandle($categoryTitle);
        $query = Category::find()
            ->groupId($group->id)
            ->one();

        return $query;
    }

    /*
     *
     * @param string $entryTitle
     */
    public function queryFindEntryCategory($category, string $entryTitle)
    {
        $query = Category::find()
            ->search($entryTitle)
            ->one();

        var_dump($query);
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
