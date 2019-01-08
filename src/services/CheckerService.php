<?php

/**
 *  
 */

namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use yii\base\Component;

ini_set('max_execution_time', 10000);

class CheckerService extends Component
{
    private $section = '',
        $duplicates = [],
        $allEntries = [];
    /*
     *  find string in array data
     *
     *  @param array $haystack
     *  @param string $needle
     *  @param int $offset
     */
    public function getAllDuplicates($section) 
    {
        $this->section = $section;

        $this->getDuplicates();

        return $this->duplicates;
    }

    /**
     * Get all Duplicates and show their enabled status
     */
    public function getAllDuplicatesWithStatus($section) 
    {
        $this->section = $section;

        $this->getDuplicates();

        $duplicates = [];
        foreach($this->duplicates as $entry)
        {
            $title = $entry['title'];
            // Search for our duplicate
            $query = Triton::getInstance()->queryService->queryEntryByTitle($entry); 

            $duplicates[$title]['title'] = $title;
            foreach($query as $key => $data) {
                $duplicates[$title]['status_' . $key] = $data->enabled;

                // If the entry is disabled but not locked, it should be!
                if($data->enabled === '0' && $data->lock === 0)
                {
                    //$this->lockEntry($data); 
                }
            }
        }

        return $duplicates;
    }

    public function deleteDuplicates($section)
    {
        $this->section = $section;

        $this->getDuplicates();

        $duplicates = [];
        $deleted = [];

        foreach($this->duplicates as $entry)
        {
            $title = $entry['title'];
            // Search for our duplicate
            $query = Triton::getInstance()->queryService->queryEntryByTitle($entry); 

            $duplicates[$title]['title'] = $title;
            foreach($query as $key => $data) {
                $duplicates[$title]['status_' . $key] = $data->enabled;

                if($data->enabled === '1' && $key > 0)
                {
                    $deleted[] = $data->title;
                    $this->deleteEntry($data); 
                }
            }
        }

        return $deleted;
    }

    private function getDuplicates()
    {
        $allEntryTitles = [];
        $duplicates = [];
        $this->allEntries = Triton::getInstance()->queryService->getAllEntriesUntouched($this->section);

        foreach($this->allEntries as $entry)
        {
            if(isset($allEntryTitles[$entry->title])) {
                $duplicates[] = [
                    'title' => $entry->title
                ];
            }
            $allEntryTitles[$entry->title] = $entry->title;

            // If the entry is disabled but not locked, it should be!
            if($entry->enabled === '0')
            {
                $this->lockEntry($entry); 
            }
        }

        $this->duplicates = $duplicates;
    }

    private function lockEntry($entry)
    {
        $entry->lock = 1;

        if(Craft::$app->elements->saveElement($entry)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
    }

    private function deleteEntry($entry)
    {
        if(Craft::$app->elements->deleteElement($entry)) {
            return true;
        } else {
            throw new \Exception("Saving failed: " . print_r($entry->getErrors(), true));
        }
    }
} 
