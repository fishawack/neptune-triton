<?php

/*
 *  Json Custom Class
 *  =================
 *
 *  There's a few things in our Benlysta
 *  project that requires custom values.
 *
 *  Use this class here to sort these out
 *  and return back into the JsonService
 *
 */

namespace fishawack\triton\services;

use fishawack\triton\Triton;
use Craft;
use yii\base\Component;

class JsonCustomService extends Component
{
    /*
     * We simplify our document status
     * down to 2 types. Use this
     * class to return the correct one.
     */
    public function getCustomDocumentStatus(string $docStatus)
    {
        $publicationTypes = Triton::getInstance()->variablesService->getPubCustomVars();

        if(!in_array($docStatus, $publicationTypes))
        {
            return "Planned";
        }
        return "Published";
    }
}
