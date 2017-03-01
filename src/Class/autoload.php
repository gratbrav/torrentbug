<?php
/**
 * Autoloader
 *
 * Autoload required class
 *
 * @package  Torrentbug
 * @author   Gratbrav
 */
class Autoloader
{
    /**
     * Constructor
     */
    public function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Create autoloader
     */
    public static function register()
    {
        new Autoloader();
    }

    /**
     * Load class
     * 
     * @param string $className
     */
    public function loadClass($className)
    {
        $file = str_replace('Gratbrav\\Torrentbug\\', '', $className);
        $file = str_replace('\\', '/', $file);
        $file = __DIR__ . '/' . $file . '.php';

        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->fallback($className);
        }
    }

    /**
     * Fallback to load class without namespace
     * 
     * @param string $className
     */
    protected function fallback ($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        require __DIR__ . '/../' . $fileName;
    }
}

Autoloader::register();
