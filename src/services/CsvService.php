<?php

/**
 *  A simple class that will setup
 *  and put our CSV values into a nice
 *  array to be processed
 *  
 *  1MS, 2MS, RA use journals
 */

namespace fishawack\triton\services;

use fishawack\triton\Triton;
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
     *
     *  @param string $filePath
     */
    public function checkCsvType(string $filePath)
    {
        $csvFile = file($filePath);
        
        $csvType = explode(' ', $csvFile[1]);

        return $csvType[1];
    } 

    /*
     * Read file into memory and turn into
     * an array
     *
     * TODO 
     * 1.Change this is so that variables are read
     * from our variablesService
     * 2. Integrate hooking feature so that we can
     * inject code to minipulate variables
     *
     * @param string $filePath
     */
    public function publicationCsvToArray(string $filePath)
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

            /*
             *  Benlysta specific
             *
             *  Check if the Document status
             *  is accepted via our variables 
             *  service
             */
            $acceptedValues = Triton::getInstance()->variablesService->acceptedDocTypes();

            $docType = trim(mb_convert_encoding($expandCsv[7], "UTF-8"));

            if(!in_array($docType, $acceptedValues))
            {
                continue;
            }

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

            $title = trim(mb_convert_encoding($expandCsv[0], "UTF-8"));

            // Setup all the keys correctly
            $data[$expandCsv[0]]['title'] = $title;
            $data[$expandCsv[0]]['documentTitle'] = trim(mb_convert_encoding($expandCsv[1], "UTF-8"));
            $data[$expandCsv[0]]['documentStatus'] = trim(mb_convert_encoding($expandCsv[2], "UTF-8"));
            //
            $data[$expandCsv[0]]['startDate'] = $startDate;
            $data[$expandCsv[0]]['submissionDate'] = $submissionDate;
            $data[$expandCsv[0]]['documentAuthor'] = trim(mb_convert_encoding($expandCsv[5], "UTF-8"));
            $data[$expandCsv[0]]['documentType'] = trim(mb_convert_encoding($expandCsv[7], "UTF-8"));
            $data[$expandCsv[0]]['docType'] = trim(mb_convert_encoding($expandCsv[7], "UTF-8"));
            $data[$expandCsv[0]]['citation'] = trim(mb_convert_encoding($expandCsv[8], "UTF-8"));
            $data[$expandCsv[0]]['citationUrl'] = trim(mb_convert_encoding($expandCsv[9], "UTF-8"));
            $data[$expandCsv[0]]['publicationDate'] = $publicationDate;

            /*
             * Benlysta specific implementation,
             * this should also be in here for later 
             * since no one knows what a HVT Abstract is
             *
             * TODO
             * Further down the line maybe we should 
             * implement some event system to inject code
             * from a service
             */
            if($data[$expandCsv[0]]['documentType'] == 'HVT Abstract')
            {
                $data[$expandCsv[0]]['documentType'] = 'Abstract';
                $data[$expandCsv[0]]['docType'] = 'Abstract';
            }

            // Check if we need Journal or Congress
            if($this->strposa($title, Triton::getInstance()->variablesService->journalPubs()))
            {
                $data[$expandCsv[0]]['journal'] = trim(mb_convert_encoding($expandCsv[6], "UTF-8"));
            } else {
                $data[$expandCsv[0]]['congress'] = trim(mb_convert_encoding($expandCsv[6], "UTF-8"));
            }
            

            // Clean & expand studies
            $studies = $this->removeHTMLTags($this->removeHTMLTags($expandCsv[11]));
            $studies = explode(' ', $studies);
            $data[$expandCsv[0]]['study'] = $this->clearEmptyArrayValues($studies);
        } 
        return $data;
    }

    /**
     * Journale Congress Study csv to array
     *
     * @param string $sectionTitle
     * @param string $filePath
     */
    public function jscCsvToArray(string $sectionTitle, string $filePath)
    {
        $csvFile = file($filePath);

        // Remove the misc fields from dv
        $cleanData = $this->cleanCsv($csvFile);

        // Check to make sure that our arrays match 
        // in size.
        //
        // We have to unset the title for the exploded
        // array to match in size - DV doesn't provide
        // you with Craft titles which we will load in
        //
        // $titlePosition is which position within the 
        // array is for the title to be saved
        $titlePosition = 0;
        switch ($sectionTitle)
        {
            case "studies":
                $titlePosition = 2;
                $JSCArray = Triton::getInstance()->variablesService->getStudyHeaders();
                break;
            case "journals":
                $JSCArray = Triton::getInstance()->variablesService->getJournalHeaders();
                break;
            case "congresses":
                $JSCArray = Triton::getInstance()->variablesService->getCongressHeaders();
                break;
        }

        $data = [];

        foreach($cleanData as $result)
        {
             // Put all out data into an array by
            // delimiting the `
            $expandCsv = [];
            $expandCsv =  explode('`', $result);

            // Check if the arrays match
            if(count($expandCsv)!== count($JSCArray))
            {
                throw new \Exception("Array doesn't match for ".$sectionTitle);
            }

            // Merge the two arrays to use variables
            // as keys
            $fusion = [];
            for($i=0; $i < count($JSCArray); $i++)
            {
                $fusion[$JSCArray[$i]] = $expandCsv[$i];

            }

            foreach($fusion as $key => $value) 
            {
                // Check to see if the 
                // data is supposed to be saved
                // as a date.
                if (stripos($key, 'date') !== false && $value !== '')
                { 
                   $fusion[$key] = date('Y-m-d H:i:s', strtotime($value));
                } else {
                   $fusion[$key] = trim(mb_convert_encoding($value, "UTF-8"));
                }
            }

            $data[$fusion['title']] = $fusion;
        }

        return $data;
    }

    /*
     *  Check if it's a Chai datavision export,
     *  we need to remove the first two elements
     *  of the array
     *
     *  @param array $csvArray
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
     *
     * @param string $data
     */
    protected function removeHTMLTags($data)
    {
        $clean = strip_tags($data);
        return $clean;
    }

    /*
     * clear empty array values
     *
     * @param array $dataArray
     */
    protected function clearEmptyArrayValues(array $dataArray)
    {
        $returnArray = [];
        foreach ($dataArray as $data)
        {
            // clear all white space
            //$data = str_replace(' ', '', $data);
            $data = trim($data);

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

    /*
     *  find string in array data
     *
     *  @param array $haystack
     *  @param string $needle
     *  @param int $offset
     */
    public function strposa($haystack, $needle, $offset=0) 
    {
        if(!is_array($needle)) 
        {
            $needle = array($needle);
        }

        foreach($needle as $query) 
        {
            if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
        }
        return false;
    }
}   
