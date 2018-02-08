<?php

/**
 *  TODO
 *
 *  1MS, 2MS, RA starting titles are journals,
 *  everything else is congress
 */

namespace fishawack\triton\services;

use Craft;

use yii\base\Component;

class CsvService extends Component
{
    /*
     *  Check CSV type, we need to see
     *  if the CSV is a Journal, Study, Congress
     *  or Publication
     *
     *  The 2nd row of the data from the CSV
     *  should be in the format:
     *
     *  "Chai's $csvType Test"
     */
    public function checkCsvType($filePath)
    {
        $csvFile = file($filePath);
        
        $csvType = explode(' ', $csvFile[1]);

        // TODO
        //
        // Define global variables in a seperate file
        // for better flexibility
        return $csvType[1];
    } 

    /*
     * Read file into memory and turn into
     * an array
     */
    public function publicationCsvToArray($filePath)
    {
        $csvFile = file($filePath);

        // Remove the misc fields from dv
        $cleanData = $this->cleanCsv($csvFile);

        $data = [];
        
        // $i = 2 because we've deleted the first
        // 2 rows, don't want to use more memory
        // reindexing etc when we will not need it
        // afterwards
        foreach($cleanData as $result)
        {
            $expandCsv = [];

            // $expandCsv[0] is the title
            $expandCsv =  explode('`', $result);

            $startDate = null;
            if(strlen($expandCsv[3]) > 0)
            {
                $startDate = date('Y-m-d H:i:s', strtotime($expandCsv[3]));
            }

            $submissionDate = null;
            if(strlen($expandCsv[4]) > 0)
            {
                $submissionDate = date('Y-m-d H:i:s', strtotime($expandCsv[4]));
            }

            $publicationDate = null;
            if(strlen($expandCsv[10]) > 0)
            {
                $submissionDate = date('Y-m-d H:i:s', strtotime($expandCsv[10]));
            }            

            // Setup all the keys correctly
            $data[$expandCsv[0]]['title'] = mb_convert_encoding($expandCsv[0], "UTF-8");
            $data[$expandCsv[0]]['documentTitle'] = mb_convert_encoding($expandCsv[1], "UTF-8");
            $data[$expandCsv[0]]['documentStatus'] = mb_convert_encoding($expandCsv[2], "UTF-8");
            $data[$expandCsv[0]]['startDate'] = $startDate;
            $data[$expandCsv[0]]['submissionDate'] = $submissionDate;
            $data[$expandCsv[0]]['documentAuthor'] = mb_convert_encoding($expandCsv[5], "UTF-8");
            $data[$expandCsv[0]]['journal'] = mb_convert_encoding($expandCsv[6], "UTF-8");
            $data[$expandCsv[0]]['documentType'] = mb_convert_encoding($expandCsv[7], "UTF-8");
            $data[$expandCsv[0]]['citation'] = mb_convert_encoding($expandCsv[8], "UTF-8");
            $data[$expandCsv[0]]['citationUrl'] = mb_convert_encoding($expandCsv[9], "UTF-8");
            $data[$expandCsv[0]]['publicationDate'] = $publicationDate;

            // Clean & expand studies
            $studies = $this->removeHTMLTags($this->removeHTMLTags($expandCsv[11]));
            $studies = explode(' ', $studies);
            $data[$expandCsv[0]]['study'] = $this->clearEmptyArrayValues($studies);
        } 
        return $data;
    }

    /**
     *  Read Csv into Array for studies
     */
    public function studiesCsvToArray($filePath)
    {
        $csvFile = file($filePath);

        // Remove the misc fields from dv
        $cleanData = $this->cleanCsv($csvFile);

        $data = [];

        foreach($cleanData as $result)
        {
            $expandCsv = [];
            $expandCsv =  explode('`', $result);

            
            $sacDate = null;
            if(strlen($expandCsv[1]) > 0)
            {
                $sacDate = date('Y-m-d H:i:s', strtotime($expandCsv[1]));
            }
            
            $data[$expandCsv[2]]['title'] = mb_convert_encoding($expandCsv[2], "UTF-8");
            $data[$expandCsv[2]]['sacDate'] = $sacDate;
            $data[$expandCsv[2]]['studyTitle'] =  mb_convert_encoding($expandCsv[0], "UTF-8");
        }
        return $data;
    }

    /**
     *  Read Csv into Array for journals
     */
    public function journalsCsvToArray($filePath)
    {
        $csvFile = file($filePath);

        // Remove the misc fields from dv
        $cleanData = $this->cleanCsv($csvFile);

        $data = [];
        
        // $i = 2 because we've deleted the first
        // 2 rows, don't want to use more memory
        // reindexing etc when we will not need it
        // afterwards
        foreach($cleanData as $result)
        {
        }
    }

    /**
     *  Read Csv into Array for congress     
     */
    public function congressCsvToArray($filePath)
    {
        $csvFile = file($filePath);

        // Remove the misc fields from dv
        $cleanData = $this->cleanCsv($csvFile);

        $data = [];
        
        // $i = 2 because we've deleted the first
        // 2 rows, don't want to use more memory
        // reindexing etc when we will not need it
        // afterwards
        foreach($cleanData as $result)
        {
        }
    }

    /*
     *  Check if it's a Chai datavision export,
     *  we need to remove the first two elements
     *  of the array
     */
    protected function cleanCsv(array $csvArray)
    {
        $cleanedArray = $csvArray;
        /*
         *  TODO: Check for Chais data
         */

        // Remove first 2 and last element
        unset($cleanedArray[0]);
        unset($cleanedArray[1]);

        // Remove publication bottom footer
        array_pop($cleanedArray);
    
        return $cleanedArray;
    }

    /**
     * Remove HTML entities etc
     */
    protected function removeHTMLTags($data)
    {
        $clean = strip_tags($data);
        return $clean;
    }

    protected function clearEmptyArrayValues(array $dataArray)
    {
        $returnArray = [];
        foreach ($dataArray as $data)
        {
            // clear all white space
            $data = str_replace(' ', '', $data);

            // Studies should alwasy have more than
            // 2 characters whilst sometimes we can't 
            // catch all the random white spaces
            if(strlen($data) > 2) 
            {
                $returnArray[] = mb_convert_encoding($data, "UTF-8");
            }
        }
        return $returnArray;
    }
}   
