# Triton plugin for Craft CMS 3.x

Since our product is called Neptune, it made sense to name our plugin following suit as Triton is the largest moon for Neptune!

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

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

## Configuring Triton

This tool is so automated that there's only one thing you have to do!

## Using Triton

Make sure that you export CSV from Datavision with no text quantifier and use ` for seperation

## Triton Roadmap

Some things to do, and ideas for potential features:
* Export Db before an import happens
    * Delete SQL files that are older than specific time or limit the number of files available
* Auto check for studies / journals / congress - DONE!
* Auto Finish templating the front end of the app - DONE!
* Setup routes to show json of publications, congress, journals, studies & tags - DONE!
* Create cache files for speedier loading - DONE!
* Release it - DONE!

Brought to you by [GeeHim Siu](www.fishawack.com)
