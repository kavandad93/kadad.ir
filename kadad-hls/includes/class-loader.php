<?php
/**
 * Class Loader
 * Handles autoloading of plugin classes
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Loader
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_autoloader();
    }

    /**
     * Register autoloader
     */
    private function register_autoloader()
    {
        spl_autoload_register([$this, 'load_class']);
    }

    /**
     * Load class file
     */
    public function load_class($class)
    {
        // Only load our plugin classes
        if (strpos($class, 'KHC_') !== 0) {
            return;
        }

        $file = KHC_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', substr($class, 4))) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
}