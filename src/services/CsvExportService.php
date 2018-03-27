<?php

/*
 *
 *
 */
namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use craft\elements\Entry;
use yii\base\Component;

class CsvExportService extends Component
{
    private $allPublications;

    public function __construct()
    {
        $this->allPublications = Triton::getInstance()->queryService->queryAllEntries('publications', $status = null);
    }

    public function exportCsv()
    {
        // Get headers
        $headers = Triton::getInstance()->variablesService->getPublicationExportCsvHeaders();
        $body = $this->setupDataForCsv();
        $this->checkFolder();
        return $export = $this->writePublicationsExportCsv($headers, $body);
    }

    public function setupDataForCsv()
    {
        $data = [];
        foreach($this->allPublications as $pub)
        {
            $data[$pub->id]['title'] = Triton::getInstance()->encodingService->toUTF8((string)$pub->title);
            $data[$pub->id]['documentTitle'] = Triton::getInstance()->encodingService->toUTF8((string)$pub->documentTitle);
            $data[$pub->id]['documentStatus'] = (string)$pub->documentStatus;
            if($pub->startDate)
            {
                $data[$pub->id]['startDate'] = $pub->startDate->format('Y-m-d');
            } else {
                $data[$pub->id]['startDate'] = '';
            }
            if($pub->submissionDate)
            {
                $data[$pub->id]['submissionDate'] = $pub->submissionDate->format('Y-m-d');
            } else {
                $data[$pub->id]['submissionDate'] = '';
            }
            $data[$pub->id]['documentAuthor'] = Triton::getInstance()->encodingService->toUTF8((string)$pub->documentAuthor);
            $data[$pub->id]['documentType'] = (string)$pub->documentType;
            if(isset($pub->docType))
            {
                $docType = '';
                foreach($pub->docType as $type)
                {
                    $docType .= (string)$type->title;
                }

                $data[$pub->id]['docType'] = $docType;
            } else {
                $data[$pub->id]['docType'] = '';
            }
            if(isset($pub->journal))
            {
                $journalList = '';
                foreach($pub->journal as $journal)
                {
                    $journalList .= (string)$journal->title;
                }
                $data[$pub->id]['journal'] = $journalList;
            } else {
                $data[$pub->id]['journal'] = '';
            }
            if(isset($pub->congress))
            {
                $congressList = '';
                foreach($pub->congress as $congress)
                {
                    $congressList .= (string)$congress->title;
                }
                $data[$pub->id]['congress'] = $congressList;
            } else {
                $data[$pub->id]['congress'] = '';
            }
            $data[$pub->id]['citation'] = (string)$pub->citation;
            $data[$pub->id]['citationUrl'] = (string)$pub->citationUrl;
            if($pub->publicationDate)
            {
                $data[$pub->id]['publicationDate'] = $pub->publicationDate->format('Y-m-d');
            } else {
                $data[$pub->id]['publicationDate'] = '';
            }
            if(isset($pub->study))
            {
                $studies = '';

                for($i=0; $i < count($pub->study); $i++)
                {
                    $studies .= (string)$pub->study[$i]->title;
                    if($i < (count($pub->study) - 1))
                    {
                        $studies .= ", ";
                    }
                }

                $data[$pub->id]['study'] = $studies;
            } else {
                $data[$pub->id]['study'] = '';
            }
            if(isset($pub->category))
            {
                $category = '';
                for($i=0; $i < count($pub->category); $i++)
                {
                    $category .= (string)$pub->category[$i]->title;
                    // Make sure we seperate the data with commas
                    // unless we're at the 2nd to last
                    if($i < (count($pub->category) - 1))
                    {
                        $category .= ", ";
                    }
                }

                $data[$pub->id]['category'] = $category;
            } else {
                $data[$pub->id]['category'] = '';
            }
            if(isset($pub->relatedPubs))
            {
                $related = '';
                for($i=0; $i < count($pub->relatedPubs); $i++)
                {
                    $studies .= (string)$pub->relatedPubcs[$i]->title;
                    // Make sure we seperate the data with commas
                    // unless we're at the 2nd to last
                    if($i < (count($pub->relatedPubs) - 1))
                    {
                        $studies .= ", ";
                    }
                }

                $data[$pub->id]['related'] = $related;
            } else {
                $data[$pub->id]['related'] = '';
            }
            if(isset($pub->summary))
            {
                $data[$pub->id]['summary'] = strip_tags((string)$pub->summary);
            } else {
                $data[$pub->id]['summary'] = '';
            }
            if(isset($pub->objectives))
            {
                $data[$pub->id]['objectives'] = strip_tags((string)$pub->objectives);
            } else {
                $data[$pub->id]['objectives'] = '';
            }
            if(isset($pub->publicationTags))
            {
                $pubTags = '';

                for($i=0; $i < count($pub->publicationTags); $i++)
                {
                    $pubTags .= (string)$pub->publicationTags[$i]->title;
                    if($i < (count($pub->publicationTags) - 1))
                    {
                        $pubTags .= ", ";
                    }
                }

                $data[$pub->id]['publicationTags'] = $pubTags;
            } else {
                $data[$pub->id]['publicationTags'] = '';
            }

            if($pub->lock == '1')
            {
                $lock = 'Locked';
            } else {
                $lock = '';
            }

            $data[$pub->id]['lock'] = $lock;

            if($pub->enabled == '1')
            {
                $enabled = 'Enabled';
            } else {
                $enabled = 'Disabled';
            }

            $data[$pub->id]['enabled'] = $enabled;

        }
        return $data;
    }
    /*
     *  Check if folder/file exists,
     *  if not create it
     *
     *  @param string $filepath
     */
    protected function checkFolder()
    {
        if(!file_exists('csvexport/'))
        {
            mkdir('csvexport/', 0755);
        }
    }


    /*
     *  Write data to file
     *
     *  @param 
     */
    public function writePublicationsExportCsv($headers, $data)
    {
        $filePath = Triton::getInstance()->variablesService->getJsonLinks();
        $outputToFile = fopen($filePath['exportcsv']['path'], 'w');
        if($outputToFile === false)
        { 
            throw new \Exception('Cannot open file');
        }

        fputcsv($outputToFile, $headers);

        foreach($data as $row)
        {
            fputcsv($outputToFile, $row);
        }

        fclose($outputToFile);

        return true;
    }

    /*
     *  Set download headers
     */
    public function getDownloadHeaders($filename)
    {
        // force download  
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}
