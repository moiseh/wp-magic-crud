<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit14b5997af31c3bb8eef6a5f4ff35143e
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPQueryBuilder\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPQueryBuilder\\' => 
        array (
            0 => __DIR__ . '/..' . '/stephenharris/wp-query-builder/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit14b5997af31c3bb8eef6a5f4ff35143e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit14b5997af31c3bb8eef6a5f4ff35143e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
