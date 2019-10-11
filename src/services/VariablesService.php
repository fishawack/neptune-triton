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
            'docType',
            'citation',
            'citationUrl',
            'publicationDate',
            'study'    
        ];
    }

    // Headers for CSV
    public function getPublicationExportCsvHeaders()
    {
        return [
            'Title',
            'Product',
            'Document Title',
            'Document Status',
            'Start Date',
            'Submission Date',
            'Document Author',
            'Document Type',
            'Doc Type',
            'Journals',
            'Congresses',
            'Citation',
            'Citation Url',
            'Publication Dates',
            'Study',
            'Category',
            'Related Pubs',
            'Summary',
            'Objectives',
            'Publication Tags',
            'Lock',
            'Enabled'
            
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

    // CSV Import, ignored document types
    public function acceptedDocTypes()
    {
        return [
            'Abstract',
            'HVT Abstract',
            'Primary Manuscript',
            'Secondary Manuscript',
            'Review Article'
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

    //  What entries we need to disregard
    //  via status
    public function getDisregards()
    {
        $list[] = [
            'handle' => 'documentStatus',
            'ignore' => [
                'Canceled',
                'Rejected'
            ]
        ];
        return $list;
    }

    public function getJsonLinks()
    {
        return [
            'publications' => [
                'url' => 'triton/publications', 
                'path' => 'json/publications'
            ],
            'studies' => [
                'url' => 'triton/studies',
                'path' => 'json/studies'
            ],
            'journals' => [
                'url' => 'triton/journals',
                'path' => 'json/journals'
            ],
            'congresses' => [
                'url' => 'triton/congresses',
                'path' => 'json/congresses'
            ],
            'categories' => [
                'url' => 'triton/categories',
                'path' => 'json/categories'
            ],            
            'globals' => [
                'url' => 'triton/global',
                'path' => 'json/global'
            ],
            'products' => [
                'url' => 'triton/products',
                'path' => 'json/products'
            ],
            'doctypes' => [
                'url' => 'triton/doc-types',
                'path' => 'json/doc-types'
            ],            
            'tags' => [
                'url' => 'triton/tags',
                'path' => 'json/tags'
            ],
            'exportcsv' => [
                'url' => 'triton/csvexport',
                'path' => 'csvexport/publication.csv'
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
            'statusDatavision' => 'documentStatus',
            'datavisionStatus' => 'dvStatus',
            'title' => 'documentTitle',
            'type' => 'documentType',
            'publicationDate' => 'publicationDate',
            'startDate' => 'startDate',
            'studies' => 'study',
            'submissionDate' => 'submissionDate',
            'categories' => 'category',
            'related' => 'relatedPubs',
            'enabled' => 'enabled',
            'keyPublication' => 'keyPublication',
            'summary' => 'summary',
            'product' => 'product',
            'objectives' => 'objectives',
            'tags' => 'publicationTags',
            'file' => 'file',
            'lock' => 'lock',
            'custom' => [
                'status' => [
                    'function' => 'getCustomDocumentStatus',    
                    'craftName' => 'documentStatus',
                    'jsonName' => 'status'
                ]
            ]
        ];
    }

    /*
     * Structure of arrays coming from appends
     */
    public function getAppendDataStructure() 
    {
        return [
            'title',
            'product',
            'summary',
            'category',
            'publicationTags',
            'file',
            'congress'
        ];
    }


    /*
     *  Use this method to generate
     *  any arrays needed for retrieving
     *  data
     *
     */
    public function getPubCustomVars()
    {
        return [
            'Completed',
            'Presented',
            'ePub Only',
            'ePub Ahead of Print',
            'Published'
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

    public function getProductsJsonStruc()
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'image' => 'image'
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
            'publications' => 'actionGetAllPublications',
            'studies' => 'actionGetAllStudies',
            'journals' => 'actionGetAllJournals',
            'congresses' => 'actionGetAllCongresses',
            'categories' => 'actionGetAllCategories',
            'globals' => 'actionGetAllGlobals',
            'products' => 'actionGetAllProducts',
            'doctypes' => 'actionGetAllDoctypes',
            'tags' => 'actionGetAllTags'
        ];
    }


    /**
     * Checker variables
     */
    public function getSectionTitles()
    {
        return [
            'publications',
            'studies',
            'journals',
            'congresses'
        ];
    }
}
