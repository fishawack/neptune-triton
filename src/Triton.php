<?php
/**
 * Triton plugin for Craft CMS 3.x
 *
 * JSON export for Neptune
 *
 * @link      www.fishawack.com
 * @copyright Copyright (c) 2018 GeeHim Siu
 */

namespace fishawack\triton;


use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    GeeHim Siu
 * @package   Triton
 * @since     1.0.0
 *
 */
class Triton extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Triton::$plugin
     *
     * @var Triton
     */
    public static $plugin;
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Triton::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        
        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['triton/publications'] = 'triton/default/get-all-publications';
                $event->rules['triton/journals'] = 'triton/default/get-all-journals';
                $event->rules['triton/congresses'] = 'triton/default/get-all-congresses';
                $event->rules['triton/studies'] = 'triton/default/get-all-studies';
                $event->rules['triton/tags'] = 'triton/default/get-all-tags';
                $event->rules['triton/categories'] = 'triton/default/get-all-categories';
                $event->rules['triton/globals'] = 'triton/default/get-all-globals';
                $event->rules['triton/doctypes'] = 'triton/default/get-all-doctypes';

                // Update Json Cache files
                $event->rules['triton/updatejsonfiles'] = 'triton/default/update-json-cache';

                $event->rules['triton/dynamic'] = 'triton/default/dynamic';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['/triton/upload'] = 'triton/import/init-import';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        // Setup services
        $this->setComponents([
            'tritonAssets' => services\TritonAssets::class,
            'csvService' => services\CsvService::class,
            'entryService' => services\EntryService::class,
            'entryChangeService' => services\EntryChangeService::class,
            'studiesService' => services\StudiesService::class,
            'jscImportService' => services\JSCImportService::class,
            'jsonService' => services\JsonService::class,
            'variablesService' => services\VariablesService::class,
            'queryService' => services\QueryService::class
        ]);

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'triton',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'import' => ['label' => 'Import', 'url' => 'triton/']
        ];

        return $item;
    }

    // Protected Methods
    // =========================================================================

}
