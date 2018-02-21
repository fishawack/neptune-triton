<?php

/*
 *  This is our variables fill, define anything
 *  you need and call it through. Currently using
 *  it to store header arrays which in theory Craft
 *  should be around to return for us.
 *
 *  Change this when we go for a refactor later on
 */
namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use yii\base\Component;

Class VariablesService extends component
{
    /*
     *  get section headers, the array
     *  has mimic the structure of the 
     *  excel sheet, these will be keys
     */
    public function getPublicationHeaders()
    {
        return [
            'title',
            'documentTitle',
            'documentStatus',
            'startDate',
            'submissionDate',
            'documentAuthor',
            'documentType',
            'citation',
            'citationUrl',
            'publicationDate',
            'study'    
        ];
    }

    public function getJournalHeaders()
    {
        return [
            'title'
        ];
    }

    public function getCongressHeaders()
    {
        return [
            'title',
            'abstractDueDate',
            'fromDate',
            'toDate',
            'congressAcronym'
        ];
    }

    public function getStudyHeaders()
    {
        return [
            'studyTitle',
            'sacDate',
            'title'
        ];
    }

    public function getTagHeaders()
    {
        return [
            'title'
        ];
    }

    public function getGlobalHeaders()
    {
        return [
            'DVDate'
        ];
    }

    public function getDocTypeHeaders()
    {
        return [
            'title'
        ];
    }

    public function getCategoryHeaders()
    {
        return [
            'title'
        ];
    }

    // Array of search terms that needs to be
    // matched. Matched variables will use
    // journals instead of congresses
    public function journalPubs()
    {
        return [
            '1MS',
            '2MS',
            'RA'
        ];
    }

    public function getJsonLinks()
    {
        return [
            'publications' => [
                'url' => 'triton/publications', 
                'path' => 'json/publication.json'
            ],
            'studies' => [
                'url' => 'triton/studies',
                'path' => 'json/studies.json'
            ],
            'journals' => [
                'url' => 'triton/journals',
                'path' => 'json/journals.json'
            ],
            'congresses' => [
                'url' => 'triton/congresses',
                'path' => 'json/congresses.json'
            ],
            'categories' => [
                'url' => 'triton/categories',
                'path' => 'json/categories.json'
            ],            
            'globals' => [
                'url' => 'triton/globals',
                'path' => 'json/globals.json'
            ],
            'doctypes' => [
                'url' => 'triton/doctypes',
                'path' => 'json/doctypes.json'
            ],            
            'tags' => [
                'url' => 'triton/tags',
                'path' => 'json/tags.json'
            ]
        ];
    }

    /*
     *  Json formatting, the keys for our
     *  json files differ to that of Craft
     *
     *  Place the structure of the json files
     *  here
     */
    public function getPublicationJsonStruc()
    {
        return [
            'id' => 'id',
            'docNum' => 'title',
            'author' => 'documentAuthor',
            'citation' => 'citation',
            'citationUrl' => 'citationUrl',
            'congress' => 'congress',
            'journal' => 'journal',
            'status' => 'documentStatus',
            'datavisionStatus' => 'dvStatus',
            'title' => 'documentTitle',
            'type' => 'documentType',
            'publicationDate' => 'publicationDate',
            'startDate' => 'startDate',
            'studies' => 'study',
            'submissionDate' => 'submissionDate',
            'categories' => 'category',
            'related' => 'relatedPubs',
            'keyPublication' => 'keyPublication',
            'summary' => 'summary',
            'objectives' => 'objectives',
            'tags' => 'publicationTags',
            'lock' => 'lock'
        ];
    }

    public function getCongressJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'acronym' => 'congressAcronym',
            'due' => 'dueDate',
            'from' => 'fromDate',
            'to' => 'toDate'
        ];
    }

    public function getStudiesJsonStruc()
    {
        return [
            'id' => 'id',
            'studyNumber' => 'title',
            'title' => 'studyTitle',
            'sacDate' => 'sacDate'
        ];
    }

    public function getJournalsJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title',
        ];
    }

    public function getTagsJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title'
        ];
    }

    public function getGlobalsJsonStruc()
    {
        return [
            'updatedDate' => 'DVDate'
        ];
    }      

    public function getCategoryJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'children' => 'children'
        ];
    }

    public function getDocTypeJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title'
        ];
    }    

    /*
     * Json Function matching for update
     * json cache
     */
    public function getJsonCacheFunctionsStruc()
    {
        return [
            'publication' => 'actionGetAllPublications',
            'studies' => 'actionGetAllStudies',
            'journals' => 'actionGetAllJournals',
            'congresses' => 'actionGetAllCongresses',
            'categories' => 'actionGetAllCategories',
            'globals' => 'actionGetAllGlobals',
            'doctypes' => 'actionGetAllDoctypes',
            'tags' => 'actionGetAllTags'
        ];
    }
}


