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

    public function addChanged($title, $field)
    {
        $this->document['Changed'][$title][] = $field;
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

    public function getStatus()
    {
        return $this->document;
    }
}   

