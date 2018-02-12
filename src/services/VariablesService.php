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
    public function getPublicationArrayFields()
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

    public function journalPubs()
    {
        return [
            '1MS',
            '2MS',
            'RA'
        ];
    }
}


