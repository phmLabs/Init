<?php

namespace PhmLabs\Components\Init;

/**
 * @author Nils Langner <nils.langner@phmlabs.com>
 * @link http://www.phmlabs.com/Components/NamedParameters
 **/

use PhmLabs\Components\NamedParameters\NamedParameters;

class Init
{
    const METHOD_CONSTRUCTOR = '__construct';

    private static $globalParameters = array();

    public static function registerGlobalParameter($key, $value)
    {
        self::$globalParameters[$key] = $value;
    }

    public static function getInitInformationByClass($classname)
    {
        $rClass = new \ReflectionClass($classname);

        preg_match_all("/\* ([^@](.*))/", $rClass->getDocComment(), $matches);

        if ($matches[1]) {
            $classDoc = implode("\n", $matches[1]);
        } else {
            $classDoc = false;
        }

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
                    $type = false;
                }

                if (array_key_exists("2", $matches)) {
                    $description = trim(str_replace("$", "", $matches[2]));
                } else {
                    $description = false;
                }

                if ($rParameter->isOptional()) {
                    $defaultValue = $rParameter->getDefaultValue();
                } else {
                    $defaultValue = "";
                }

                $parameters[] = array("name" => $rParameter->getName(),
                    "description" => $description,
                    "type" => $type,
                    "default" => $defaultValue);
            }
        }

        return array("parameters" => $parameters, 'documentation' => $classDoc);
    }

    public static function getInitInformation($element)
    {
        return self::getInitInformationByClass($element["class"]);
    }

    public static function getAllInitInformation($configArray)
    {
        $infos = array();
        foreach ($configArray as $name => $element) {
            $infos[$name] = self::getInitInformation($element);
        }
        return $infos;
    }

    /**
     * @param $element
     * @return mixed
     * @throws \PhmLabs\Components\NamedParameters\Exception
     * @throws \ReflectionException
     */
    public static function initialize($element, $classParameter = 'class')
    {
        if (is_object($element)) {
            throw new Exception('The given $element parameter must be an array, class ' . get_class($element) . ' given.');
        }

        if (is_null($element)) {
            throw new Exception('The given $element parameter must be an array, null given.');
        }

        if (!array_key_exists($classParameter, $element)) {
            throw new Exception("The given array does not provide an element with '" . $classParameter . "' as key. Given keys are: " . implode(', ', array_keys($element)) . '.');
        }

        $class = $element[$classParameter];

        if (!array_key_exists('call', $element)) {
            $element['call'] = [];
        }

        if (!class_exists($class)) {
            throw new Exception("No class with name " . $class . " found");
        }

        if (array_key_exists('parameters', $element)) {
            $element['call']['init'] = $element['parameters'];
            unset($element['parameters']);
        }

        if (array_key_exists('call', $element) && array_key_exists(self::METHOD_CONSTRUCTOR, $element['call'])) {
            $object = NamedParameters::construct($class, $element['call'][self::METHOD_CONSTRUCTOR]);
            unset ($element['call'][self::METHOD_CONSTRUCTOR]);
        } else {
            $object = new $class();

            if (count($element['call']) === 0 && method_exists($object, 'init')) {
                $element['call']['init'] = [];
            }
        }

        foreach ($element['call'] as $methodName => $parameters) {
            if (method_exists($object, $methodName)) {
                $newParameters = [];
                foreach ($parameters as $key => $newParameter) {
                    if (is_array($newParameter) && array_key_exists($classParameter, $newParameter)) {
                        $newParameters[$key] = self::initialize($newParameter);
                    } else {
                        $newParameters[$key] = $newParameter;
                    }
                }

                $parameters = array_merge($newParameters, self::$globalParameters);
            } else {
                $parameters = self::$globalParameters;
            }

            NamedParameters::call([$object, $methodName], $parameters);
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
            try {
                $objects[$name] = self::initialize($element);
            } catch (\Exception $e) {
                throw new \RuntimeException('Unable to initialize "' . $name . '" (message: ' . lcfirst($e->getMessage()) . ')');
            }
        }
        return $objects;
    }
}
