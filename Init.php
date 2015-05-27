<?php

namespace PhmLabs\Components\Init;

/*
 * @author Nils Langner <nils.langner@phmlabs.com>
 * @link http://www.phmlabs.com/Components/NamedParameters
 */
use PhmLabs\Components\NamedParameters\NamedParameters;

class Init
{
    public static function getInitInformation($element)
    {
        $rClass = new \ReflectionClass($element["class"]);

        $parameters = array();

        if ($rClass->hasMethod("init")) {

            $rMethod = $rClass->getMethod("init");
            $rParameters = $rMethod->getParameters();

            $parameters = array();

            foreach ($rParameters as $rParameter) {
                $docComment = $rMethod->getDocComment();
                $pattern = "/@param(.*)" . $rParameter->getName() . "(.*)/i";
                preg_match($pattern, $docComment, $matches);

                if (array_key_exists("1", $matches)) {
                    $type = trim(str_replace("$", "", $matches[1]));
                } else {
                    $type = "not defined";
                }

                if (array_key_exists("2", $matches)) {
                    $descritpion = trim(str_replace("$", "", $matches[2]));
                } else {
                    $descritpion = "not defined";
                }

                $parameters = array("name" => $rParameter->getName(),
                    "description" => $descritpion,
                    "type" => $type);
            }
        }

        return $parameters;
    }

    public static function getAllInitInformation($configArray)
    {
        $infos = array();
        foreach ($configArray as $name => $element) {
            $infos[$name] = self::getInitInformation($element);
        }
        return $infos;
    }

    public static function initialize($element)
    {
        $class = $element['class'];

        if (!class_exists($class)) {
            throw new \RuntimeException("No class with name " . $class . " found");
        }

        $object = new $class();

        if (method_exists($object, 'init')) {
            if (array_key_exists('parameters', $element)) {
                NamedParameters::call([$object, 'init'], $element['parameters']);
            } else {
                $object->init();
            }
        }
        return $object;
    }

    /**
     * Initializes a list of objects represented by a given array
     *
     * @param $configArray
     * @return objects[]
     */
    public static function initializeAll($configArray)
    {
        $objects = array();
        foreach ($configArray as $name => $element) {
            $objects[$name] = self::initialize($element);
        }
        return $objects;
    }
}
