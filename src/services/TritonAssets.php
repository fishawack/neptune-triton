<?php

/*
 *
 */

namespace fishawack\triton\services;

use Craft;
use craft\web\UploadedFile;
use craft\elements\Asset;
use craft\helpers\Assets as AssetHelper;

use yii\web\BadRequestHttpException;
use yii\web\UploadFailedException;
use yii\web\UnsupportedMediaTypeHttpException;
use yii\base\Component;

class TritonAssets extends Component
{
    /*
     * Save asset to folder
     *
     * @param UploadedFile $file
     * @param int $folderId
     */
    public function saveAsset(UploadedFile $file, int $folderId)
    {
        $assets = Craft::$app->getAssets();

        if($file->type !== 'text/csv')
        {
            throw new UnsupportedMediaTypeHttpException;
        }

        $filename = AssetHelper::prepareAssetName($file->name);
        
        // Save to our Craft temp folder
        $tempPath = $file->saveAsTempFile();

        // Check if we can write to filesystem
        if($tempPath === false)
        {
            throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
        }

        // Set folder to first asset folder
        $folder = $assets->findFolder(['id' => $folderId]);

        if(empty($folder))
        {
            throw new BadRequestHttpException("Folder has not been setup! Setup 'importplugin' asset in settings > assets");
        }

        $asset = new Asset();
        $asset->filename = $filename;
        $asset->tempFilePath = $tempPath;
        $asset->newFolderId = $folder->id;
        $asset->avoidFilenameConflicts = true;

        $result = Craft::$app->getElements()->saveElement($asset);

        $filepath = $asset->getUrl();

        return $asset->id;
    }

    /*
     *  Get Asset Path
     */
    public function getAssetPath($assetId)
    {
        $assets = Craft::$app->getAssets();
        $asset = $assets->getAssetById($assetId);

        return $asset->volume->path . '/' . $asset->filename;
    }

    /*
     * Move Asset
     */
    public function moveAsset(UploadedFile $file, $location)
    {
        return "Asset has been moved";
    }

    /*
     * Replace Asset
     */
    public function replaceAsset(UploadedFile $file, $location)
    {
        return "Asset now replaced";
    }
} 
