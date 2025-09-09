<?php

class Autoloader
{
    /**
     * File extension as a string. Defaults to ".php".
     */
    protected static $fileExt = '.php';

    /**
     * The top level directory where recursion will begin. Defaults to the current
     * directory.
     */
    protected static $pathTop = __DIR__ . '/../';

    /**
     * A relative path from vendor folder of the core library source directory
     */
    protected static $coreLibSourcePath = '';

    /**
     * Autoload function for registration with spl_autoload_register
     *
     * Looks recursively through project directory and loads class files based on
     * filename match.
     *
     * @param string $className
     */
    public static function loader(string $className)
    {
        if (strpos($className, 'CAWL\\OnlinePayments\\Core') !== false) {
            $path = str_replace('CAWL\\OnlinePayments\\Core\\', '', $className);

            include_once static::$pathTop . '/vendor/' . trim(static::$coreLibSourcePath, '/') . '/' . str_replace('\\', '/', $path) . static::$fileExt;

            return;
        }

        if (strpos($className, 'CAWL\\OnlinePayments') !== false) {
            $path = str_replace('CAWL\\OnlinePayments\\', '', $className);
            $parts = explode('\\', $path);
            $firstDir = strtolower($parts[0]);
            unset($parts[0]);

            include_once static::$pathTop . $firstDir . '/' . implode('/', $parts) . static::$fileExt;
        }
    }

    /**
     * Sets the $fileExt property
     *
     * @param string $fileExt The file extension used for class files.  Default is "php".
     */
    public static function setFileExt($fileExt)
    {
        static::$fileExt = $fileExt;
    }

    /**
     * Sets the $path property
     *
     * @param string $path The path representing the top level where recursion should
     *                     begin. Defaults to the current directory.
     */
    public static function setPath($path)
    {
        static::$pathTop = $path;
    }

    public static function setCoreLibSourcePath(string $coreLibSourcePath): void
    {
        self::$coreLibSourcePath = $coreLibSourcePath;
    }

}