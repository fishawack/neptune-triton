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
     * Get one entry from selected section
     *
     * WIP / Don't use
     * @param string $sectionHandle
     */
    public function queryOneEntryByProducts(string $product)
    {
        $entries = Entry::find()
            ->section('publications')
            ->product($product)
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
     * Get one entry whether it's enabled or not
     *
     * @param string $entryId
     */
    public function queryEntryByTitle($title)
    {
        $query = Entry::find()
            ->status(null)
            ->title($title)
            ->one();

        return $query;
    }

    /*
     * Get one entry by section and title
     *
     * @param string $entryId
     */
    public function queryEntryBySectionAndTitle($title, $section)
    {
        $query = Entry::find()
            ->status(null)
            ->section($section)
            ->title($title)
            ->one();

        return $query;
    }

    /*
     * Get entry by slug!
     * Better for JSC
     */
    public function queryEntryBySlug($slug)
    {
        $query = Entry::find()
            ->anyStatus()
            ->slug($slug)
            ->one();

        return $query;
    }

    /*
     * Get entry by slug!
     * Better for JSC
     */
    public function queryEntryBySlugAndSection($slug, $sectionTitle = '')
    {
        $query = Entry::find()
            ->section($sectionTitle)
            ->anyStatus()
            ->slug($slug)
            ->one();

        return $query;
    }

    /*
     * Get one entry whether it's enabled or not by
     * title ans section
     *
     * @param string $entryId
     */
    public function queryEntryByCongressTitle($title)
    {
        $query = Entry::find()
            ->anyStatus()
            ->section('congresses')
            ->title($title)
            ->one();

        return $query;
    }

    public function queryEntryByCongressSlug($slug)
    {
        $slug = str_replace(' ', '-', $slug);
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '-', $slug);
        var_dump($slug);
        $query = Entry::find()
            ->anyStatus()
            ->section('congresses')
            ->slug($slug)
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

    public function getAllEntriesUntouchedWithProduct(string $sectionTitle, $product = '')
    {
        $entries = Entry::find()
            ->section($sectionTitle)
            ->product($product)
            ->status(null)
            ->all();

        return $entries;
    }

    public function getAllEntriesUntouched(string $sectionTitle)
    {
        $query = Entry::find()
            ->section($sectionTitle)
            ->status(null)
            ->all();

        return $query;
    }

    public function getCategory(string $categoryTitle)
    {
        return Craft::$app->getCategories()->getGroupByHandle($categoryTitle);
    }

    public function getAllCategoriesUntouched(string $categoryTitle)
    {
        // Find the correct group id
        $group = Craft::$app->getCategories()->getGroupByHandle($categoryTitle);
        $query = Category::find()
            ->groupId($group->id)
            ->all();

        return $query;
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

    public function changeTitleToSlug($title) {
        $title = str_replace(' ', '-', $title);
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '-', $title);
        return $slug;
    }
}
