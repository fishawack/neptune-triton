<?php

/*
 *
 *
 */

namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use yii\base\Component;
use craft\helpers\UrlHelper;

// Make sure allow url fopen is enabled
ini_set("allow_url_fopen", 1);

class JsonService extends Component
{
    public function updateAllJsonCache()
    {
        // Get SiteUrl
        $siteInfo = new UrlHelper;

        $allVarJson = Triton::getInstance()->variablesService->getJsonLinks();

        foreach($allVarJson as $fileData)
        {
            //$data = file_get_contents($siteInfo->siteUrl() . $fileData['url']);
            //$status = $this->writeJsonFile($data, $fileData['path']);

            $curl = curl_init($siteInfo->siteUrl() . $fileData['url']);

            // Check if file exists
            //if($this->checkFileExists($filePath) === false)
            //{
            //    $this->createFile($filePath);
            //}

            $fileWrite = fopen($fileData['path'], 'w+');

            curl_setopt($curl, CURLOPT_FILE, $fileWrite);

            curl_exec($curl);
            curl_close($curl);
            fclose($fileWrite);
        }

        return true;
    }

    /*
     *  Check if folder/file exists
     *
     *  @param string $filepath
     */
    protected function checkFileExists($filePath)
    {
        
    }

    /*
     *  Write json data to file
     *
     *  @param 
     */
    protected function writeJsonFile($data, $filePath)
    {
        $outputToFile = fopen($filePath, 'w');
        if($outputToFile === false)
        { 
            throw new \Exception('Cannot open file');
        }

        $status = fwrite($outputToFile, $data);

        if($status === false)
        {
            throw new \Exception('Something went wrong with writing file to Disk!');
        }

        return true;
    }
}
