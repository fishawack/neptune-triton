# Triton plugin for Craft CMS 3.x

Since our product is called Neptune, it made sense to name our plugin following suit as Triton is the largest moon for Neptune!


## Requirements

* PHP7+
* MySQL 5.6+
* Composer

## Installation

To install the plugin, follow these instructions.

*Pre-requistes - Make sure that the composer file in Craft has the repository that loads everything above the craft folder*

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Add this to craft's composer.json (The ../ is dependant on how deep your craft is nested)

```
  "repositories": [
    {
      "type": "path",
      "url": "../../triton"
    }
  ]
```

3. Then tell Composer to load the plugin:

        composer require /triton

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for Triton.

## Import

Must knows!

* All Products and sections will require at least 1 entry, for Bayer you will see that the each product will have a placeholder entry
* Make sure you remove the empty row from Journals.csv

## Configuring Triton

This tool is so automated that there's only one thing you have to do!

## Using Triton

Make sure that you export CSV from Datavision with no text quantifier and use ` for seperation

## Triton Roadmap

Some things to do, and ideas for potential features:
* Export Db before an import happens
    * Delete SQL files that are older than specific time or limit the number of files available
* Add in 'Products' - DONE!
* Auto check for studies / journals / congress - DONE!
* Auto Finish templating the front end of the app - DONE!
* Setup routes to show json of publications, congress, journals, studies & tags - DONE!
* Create cache files for speedier loading - DONE!
* Release it - DONE!

Brought to you by [GeeHim Siu](www.fishawack.com)


# Neptune

Since Neptune can now be installed via composer without close to zero configuration, we won't host READMEs to a repo. This readme will live here for now :)


## Workflow (Brand New)

* Create local version of the base Neptune
* Setup all configurations
* Do Triton upload of studies, journals, congresses and publications (publications MUST be imported last)
* After each import copy the import log to an excel sheet and send to writers
* Once checked everything is okay, export database
* Setup version on internal server, make sure a IT has setup urls
* Import database and check everything
* Done!

### Building from SEED

1. Setup Triton
```
git clone git@bitbucket.org:fishawackdigital/neptune-triton.git triton
```

2. Create new project using composer:

```
composer create-project craftcms/craft <Path>
```
~N.B. Triton has to sit on the same directory level as the Neptune project~

3. Add in Redactor & Triton to the project by adding this to your composer.json

* Under 'require'
```
    "fishawack/triton": "^1.0",
    "craftcms/redactor": "^1.0.1"
```

* Add new object 'repositiories'

```
"repositories": [
      {
         "type": "path",
         "url": "../triton"
      }
  ]
```

4. Run ```composer install```

5. Import Neptune SEED DB - 

6. Go to Settings > Fields > Product and add in any products this Neptune will have

7. Go to Settings > Assets Edit importplugin and make sure the File System Path points to your directory structure. (make sure that ./storage/import exists and is writable)

8. Make sure all plugins are active and that each section has a placeholder entry

9. Your Neptune is ready for DV data to be imported

### Extracting Data from DataVision

1. Acquire logins to DV
2. You'll need to setup the records to export the correct information, these templates have been exported and can be found in our Auto-Content folder (https://fishawack.egnyte.com/fl/sQKFkrYDe0).
3. Import the plugins by going to the 'Reports' tab and clicking 'Import Report' (small icon near the delete button)
4. Click Preview on each report
    * Click Export
    * Choose CSV on the left panel
    * Set column delimiter to `
    * Clear text quantifier so that it's empty
    * Check 'Export report data only'
    * Uncheck 'with column names'
    * Choose save location
    * Click OK
5. You should have exported CSVs for Journals, Congresses, Studies, Publications
6. Upload these files to Egnyte under Auto-Content / Neptune / CMS / YourNeptune

### Importing Data into Craft

__Please make sure that the import is done locally rather than the server, there isn't enough resource there sadly!__

1. Run your local installation by navigating to web/ and run:
```
php -S localhost:8000
```

2. Go to localhost:8000/admin/triton

3. Import your csv's with Publications being the absolute last and weirdly, you need to do a double import of the publications so that it's definite the journals & congresses are in.

4. Make sure you keep the reports to send to the corresponding writers
- New: New records that weren't in
- Ignored: There's been no change in the data
- Changed: Something has changed, the report will let you know what has
- Deleted: These are records that aren't in the DV import however these don't actually get deleted but disabled

5. Create an export of the CSV via the button (/admin/triton), this is a csv of the whole dataset joined with together

6. Export SQL data to be imported onto internal version

### Testing

This tool really should have some testing (PHPUnit) in place however when we first took Craft3 on it did not have tests implemented, never the less! I've created a CheckerController where you can write some
items which people usually ask for. I've started this out by writing tests for duplicate data, you can see this here: http://UrlGoeshere/triton/checker/duplicates?section=publications&status=true

Please feel free to implement anymore so that we can do run throughs to find out if the data integerity is up to scratch!


