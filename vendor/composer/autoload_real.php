<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit82dec08673d51c3c8380d9e4f8dd9a45
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit82dec08673d51c3c8380d9e4f8dd9a45', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit82dec08673d51c3c8380d9e4f8dd9a45', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit82dec08673d51c3c8380d9e4f8dd9a45::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
