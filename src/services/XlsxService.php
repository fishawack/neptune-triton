<?php

namespace fishawack\triton\services;
/**
 *  A simple class that will setup
 *  and put our CSV values into a nice
 *  array to be processed
 *  
 *  1MS, 2MS, RA use journals
 */
use fishawack\triton\Triton;
use Craft;
use yii\base\Component;
use Aspera\Spreadsheet\XLSX\Reader;
use Aspera\Spreadsheet\XLSX\SharedStringsConfiguration;

class XlsxService extends Component
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
    public function checkXlsxType(string $filePath)
    {
        $xlsxFile = file($filePath);
        
        $xlsxType = explode(' ', $xlsxFile[1]);

        return $xlsxType[1];
    } 

    /**
     *  Read CSV into PHP memory
     */
    public function readXlsxIntoArray(string $filePath)
    {
        $data = [];

        $reader = new Reader();
        $reader->open($filePath);

        foreach ($reader as $row) {
            $data[] = $row;
        }

        $reader->close();

        return $data;
    }

    /*
     * @param string $filePath
     */
    public function publicationXlsxToArray(string $filePath)
    {
        $xlsxFile = $this->readXlsxIntoArray($filePath);
        //var_dump($xlsxFile);die();

        // Remove the misc fields from dv
        $cleanData = $this->cleanXlsx($xlsxFile);

        $data = [];
        
        // $i = 2 because we've deleted the first
        // 2 rows, don't want to use more memory
        // reindexing etc when we will not need it
        // afterwards
        foreach($cleanData as $result)
        {
            /*
             *  Benlysta specific
             *
             *  Check if the Document status
             *  is accepted via our variables 
             *  service
             */
            $acceptedValues = Triton::getInstance()->variablesService->acceptedDocTypes();

            if(isset($result[7]))
            {
                $docType = trim(Triton::getInstance()->encodingService->toUTF8($result[7]));

                if(!in_array($docType, $acceptedValues))
                {
                    continue;
                }
            }

            $startDate = null;
            if(isset($result[3]) && strlen($result[3]) > 0)
            {
                $startDate = date('Y-m-d H:i:s', strtotime($result[3]));
            }

            $submissionDate = null;
            if(isset($result[4]) && strlen($result[4]) > 0)
            {
                $submissionDate = date('Y-m-d H:i:s', strtotime($result[4]));
            }

            $publicationDate = null;
            if(isset($result[10]) && strlen($result[10]) > 0)
            {
                $publicationDate = date('Y-m-d H:i:s', strtotime($result[10]));
            }            

            $title = trim(Triton::getInstance()->encodingService->toUTF8($result[0]));

            // Setup all the keys correctly
            $data[$title]['title'] = $title;
            $data[$title]['documentTitle'] = trim(Triton::getInstance()->encodingService->toUTF8($result[1]));
            $data[$title]['documentStatus'] = trim(Triton::getInstance()->encodingService->toUTF8($result[2]));
            //
            $data[$title]['startDate'] = $startDate;
            $data[$title]['submissionDate'] = $submissionDate;
            $data[$title]['documentAuthor'] = trim(Triton::getInstance()->encodingService->toUTF8($result[5]));
            $data[$title]['documentType'] = trim(Triton::getInstance()->encodingService->toUTF8($result[7]));
            $data[$title]['docType'] = trim(Triton::getInstance()->encodingService->toUTF8($result[7]));
            $data[$title]['citation'] = trim(Triton::getInstance()->encodingService->toUTF8($result[8]));
            $data[$title]['citationUrl'] = trim(Triton::getInstance()->encodingService->toUTF8($result[9]));
            $data[$title]['publicationDate'] = $publicationDate;

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
            if($data[$title]['documentType'] == 'HVT Abstract')
            {
                $data[$title]['documentType'] = 'Abstract';
                $data[$title]['docType'] = 'Abstract';
            }

            // Check if we need Journal or Congress
            if($this->strposa($title, 'Congress'))
            {
                $data[$title]['congress'] = trim(Triton::getInstance()->encodingService->toUTF8($result[6]));
            } else {
                $data[$title]['journal'] = trim(Triton::getInstance()->encodingService->toUTF8($result[6]));
            }

            // Clean & expand studies
            $studies = [
                $result[12], $result[13]
            ];

            if($studies[0] === ''
                && $studies[1] === '')
            {
                $data[$title]['study'] = ['OTH'];
            } else {
                $data[$title]['study'] = $this->clearEmptyArrayValues($studies);
            } 
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
                   $fusion[$key] = trim(Triton::getInstance()->encodingService->toUTF8($value));
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
    protected function cleanXlsx(array $xlsxArray)
    {
        $cleanedArray = $xlsxArray;
        unset($cleanedArray[0]);

        return $cleanedArray;
    }

    /**
     * Remove HTML entities etc
     *
     * @param string $data
     * @param string $excludeTags
     */
    protected function removeHTMLTags($data, string $excludeTags = '')
    {
        $clean = strip_tags($data, $excludeTags);
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
                $returnArray[] = Triton::getInstance()->encodingService->toUTF8($data);
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
