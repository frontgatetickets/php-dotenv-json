<?php
namespace jimbocoder;

use frontgatetickets\Util\JSON;

class DotenvJson {

    /**
     * Inject a configuration file's values into the environment
     * @param $path *Absolute* directory containing the config file
     * @param string $file Don't need this if you use the idiomatic default
     * @throws \InvalidArgumentException
     */
    public static function load($path, $file='.env.json')
    {
        $envFile = "$path/$file";
        $rootNode = self::_loadFromFile($envFile);

        // We choose the handler strategy up front, so we only have to make the decision once
        // instead of once per leaf.
        $leafHandler = self::_chooseLeafHandler();

        // Traverse the configuration tree and convert every leaf to the dotted syntax,
        // then add the dotted keys to the environment
        self::_traverse($rootNode, $leafHandler);

        // Also merge the values into the superglobals
        $_ENV = array_merge_recursive($_ENV, $rootNode);
        $_SERVER = array_merge_recursive($_SERVER, $rootNode);
    }

    /**
     * Parse the JSON file from the filesystem
     * @param $envFile
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @return array
     */

    protected static function _loadFromFile($envFile) {
        if ( !file_exists($envFile) ) {
            throw new \InvalidArgumentException("Environment file `$envFile` not found.");
        }

        $configTree = JSON::decode(JSON::stripComments(file_get_contents($envFile)), true);
        if ( !is_array($configTree ) ) {
            throw new \UnexpectedValueException("`$envFile` must be a JSON object or list.`");
        }
        return $configTree;

    }


    /**
     * recursively crawl the configuration tree and apply each leaf to the environment
     * @param array $root           Configuration array
     * @param callback $leafHandler Applied to each leaf node in the tree
     * @param string $prefix        Don't worry about it
     */
    protected static function _traverse($root, $leafHandler, $prefix='') {
        foreach($root as $index=>$node) {
            $key = $prefix ? "$prefix.$index" : $index;

            // Handle leaf nodes directly, otherwise recurse deeper
            if (!is_array($node)) {
                $leafHandler($key, $node);
            } else {
                self::_traverse($node, $leafHandler, $key);
            }
        }
    }


    /**
     * Detect which env facilities are available, and return a function that knows how to use them
     */
    protected static function _chooseLeafHandler() {
        if ( function_exists('apache_setenv') ) {
            return function ($k, $v) {
                putenv("$k=$v");
                apache_setenv($k, $v);
            };
        } else {
            return function($k, $v) {
                putenv("$k=$v");
            };
        }
    }

}

