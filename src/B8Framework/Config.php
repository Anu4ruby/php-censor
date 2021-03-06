<?php

namespace b8;

use Symfony\Component\Yaml\Parser as YamlParser;

if (!defined('B8_PATH')) {
    define('B8_PATH', __DIR__ . '/');
}

class Config
{
    /**
     * @var Config
     */
    protected static $instance;

    /**
     * @return Config
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $settings
     */
    public function __construct($settings = null)
    {
        self::$instance = $this;

        if (empty($settings)) {
            return;
        } elseif (is_array($settings)) {
            // Array of setting data.
            $this->setArray($settings);
        } elseif (is_string($settings) && file_exists($settings)) {
            $this->loadYaml($settings);
        }
    }

    /**
     * @param string $yamlFile
     */
    public function loadYaml($yamlFile)
    {
        // Path to a YAML file.
        $parser = new YamlParser();
        $yaml   = file_get_contents($yamlFile);
        $config = (array)$parser->parse($yaml);

        if (empty($config)) {
            return;
        }

        $this->setArray($config);
    }

    /**
     * Get a configuration value by key, returning a default value if not set.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $keyParts = explode('.', $key);
        $selected = $this->config;

        $i         = -1;
        $last_part = count($keyParts) - 1;
        while ($part = array_shift($keyParts)) {
            $i++;

            if (!array_key_exists($part, $selected)) {
                return $default;
            }

            if ($i === $last_part) {
                return $selected[$part];
            } else {
                $selected = $selected[$part];
            }
        }

        return $default;
    }

    /**
     * Set a value by key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return boolean
     */
    public function set($key, $value = null)
    {
        $this->config[$key] = $value;

        return true;
    }

    /**
     * Set an array of values.
     *
     * @param $array
     */
    public function setArray($array)
    {
        self::deepMerge($this->config, $array);
    }

    /**
     * Short-hand syntax for get()
     * @see Config::get()
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Short-hand syntax for set()
     * @see Config::set()
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function __set($key, $value = null)
    {
        return $this->set($key, $value);
    }

    /**
     * Is set
     *
     * @param string $key
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->config[$key]);
    }

    /**
     * Unset
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->config[$key]);
    }

    /**
     * Deeply merge the $target array onto the $source array. The $source array will be modified!
     *
     * @param array $source
     * @param array $target
     */
    public static function deepMerge(&$source, $target)
    {
        if (count($source) === 0) {
            $source = $target;
            return;
        }

        foreach ($target as $target_key => $target_value) {
            if (isset($source[$target_key])) {
                if (!is_array($source[$target_key]) && !is_array($target_value)) {
                    // Neither value is an array, overwrite
                    $source[$target_key] = $target_value;
                } elseif (is_array($source[$target_key]) && is_array($target_value)) {
                    // Both are arrays, deep merge them
                    self::deepMerge($source[$target_key], $target_value);
                } elseif (is_array($source[$target_key])) {
                    // Source is the array, push target value
                    $source[$target_key][] = $target_value;
                } else {
                    // Target is the array, push source value and copy back
                    $target_value[] = $source[$target_key];
                    $source[$target_key] = $target_value;
                }
            } else {
                // No merge required, just set the value
                $source[$target_key] = $target_value;
            }
        }
    }

    /**
     * @return array
     */
    public function getArray()
    {
        return $this->config;
    }
}
