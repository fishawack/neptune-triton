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
     * Read file into memory and turn into
     * an array
     */
    public function csvToArray($filePath)
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
            $data[$expandCsv[0]]['title'] = $expandCsv[0];
            $data[$expandCsv[0]]['documentTitle'] = $expandCsv[1];
            $data[$expandCsv[0]]['documentStatus'] = $expandCsv[2];
            $data[$expandCsv[0]]['startDate'] = $startDate;
            $data[$expandCsv[0]]['submissionDate'] = $submissionDate;
            $data[$expandCsv[0]]['documentAuthor'] = $expandCsv[5];
            $data[$expandCsv[0]]['journal'] = $expandCsv[6];
            $data[$expandCsv[0]]['documentType'] = $expandCsv[7];
            $data[$expandCsv[0]]['citation'] = $expandCsv[8];
            $data[$expandCsv[0]]['citationUrl'] = $expandCsv[9];
            $data[$expandCsv[0]]['publicationDate'] = $publicationDate;

            // Clean & expand studies
            $studies = $this->removeHTMLTags($this->removeHTMLTags($expandCsv[11]));
            $studies = explode(' ', $studies);
            $data[$expandCsv[0]]['study'] = $this->clearEmptyArrayValues($studies);
        } 
        return $data;
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
                $returnArray[] = $data;
            }
        }
        return $returnArray;
    }
}   
