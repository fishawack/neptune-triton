<?php

/**
 *
 */

namespace fishawack\triton\services;

use Craft;

use yii\base\Component;

class EntryChangeService extends Component
{
    /**
     * We will use our Document variable to hold
     * all the information about changes, deletes,
     * updated etc to be shown at the end of the 
     * import
     */
    public $document;

    public function addChanged($title, $field, $changes = [])
    {
        $this->document['Changed'][$title][$field]['name'] = $field;

        if(!empty($changes))
        {
            $this->document['Changed'][$title][$field]['changes']['original'] = $changes['original'];
            $this->document['Changed'][$title][$field]['changes']['new'] = $changes['new'];
        }
    }

    public function addUnchanged($title)
    {
        $this->document['Unchanged'][] = $title;
    }

    public function addLocked($title)
    {
        $this->document['Locked'][] = $title;
    }

    public function addNewEntry($title)
    {
        $this->document['New'][] = $title;
    }

    public function addDeletedEntry($title)
    {
        $this->document['Deleted'][] = $title;
    }

    public function addIgnoredEntry($title)
    {
        $this->document['Ignored'][] = $title;
    }

    public function addErrorEntry($title)
    {
        $this->document['Title Error'][] = $title;
    }

    public function addMissingEntry($title)
    {
        $this->document['Missing Journal / Congresses / Studies'][] = $title;
    }

    public function getStatus()
    {
        return $this->document;
    }
}   

