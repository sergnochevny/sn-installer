<?php

namespace sn\installer;

use Composer\Installer\LibraryInstaller;
use Composer\Script\Event;

/**
 * from yii
*/

class Installer extends LibraryInstaller
{

  /**
   * @param Event $event
   */
  public static function postCreateProject($event)
  {
    static::runCommands($event, __METHOD__);
  }

  /**
   * @param Event $event
   */
  public static function postInstall($event)
  {
    static::runCommands($event, __METHOD__);
  }

  /**
   * @param Event $event
   * @param string $extraKey
   */
  protected static function runCommands($event, $extraKey)
  {
    $params = $event->getComposer()->getPackage()->getExtra();
    if (isset($params[$extraKey]) && is_array($params[$extraKey])) {
      foreach ($params[$extraKey] as $method => $args) {
        call_user_func_array([__CLASS__, $method], (array) $args);
      }
    }
  }

  /**
   * @param array $paths
   */
  public static function setPermission(array $paths)
  {
    foreach ($paths as $path => $permission) {
      echo "chmod('$path', $permission)...";
      if (is_dir($path) || is_file($path)) {
        try {
          if (chmod($path, octdec($permission))) {
            echo "done.\n";
          };
        } catch (\Exception $e) {
          echo $e->getMessage() . "\n";
        }
      } else {
        echo "file not found.\n";
      }
    }
  }

  /**
   * @throws \Exception
   */
  public static function generateCookieValidationKey()
  {
    $configs = func_get_args();
    $key = self::generateRandomString();
    foreach ($configs as $config) {
      if (is_file($config)) {
        $content = preg_replace('/(("|\')cookieValidationKey("|\')\s*=>\s*)(""|\'\')/', "\\1'$key'", file_get_contents($config), -1, $count);
        if ($count > 0) {
          file_put_contents($config, $content);
        }
      }
    }
  }

  /**
   * @return string
   * @throws \Exception
   */
  protected static function generateRandomString()
  {
    if (!extension_loaded('openssl')) {
      throw new \Exception('The OpenSSL PHP extension is required by Yii2.');
    }
    $length = 32;
    $bytes = openssl_random_pseudo_bytes($length);
    return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
  }

  /**
   * @param array $paths
   */
  public static function copyFiles(array $paths)
  {
    foreach ($paths as $source => $target) {
      // handle file target as array [path, overwrite]
      $target = (array) $target;
      echo "Copying file $source to $target[0] - ";

      if (!is_file($source)) {
        echo "source file not found.\n";
        continue;
      }

      if (is_file($target[0]) && empty($target[1])) {
        echo "target file exists - skip.\n";
        continue;
      } elseif (is_file($target[0]) && !empty($target[1])) {
        echo "target file exists - overwrite - ";
      }

      try {
        if (!is_dir(dirname($target[0]))) {
          mkdir(dirname($target[0]), 0777, true);
        }
        if (copy($source, $target[0])) {
          echo "done.\n";
        }
      } catch (\Exception $e) {
        echo $e->getMessage() . "\n";
      }
    }
  }
}
