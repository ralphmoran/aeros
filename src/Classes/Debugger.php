<?php

namespace Aeros\Src\Classes;

/**
 * Class Debugger
 *
 * A utility class for debugging purposes.
 */
class Debugger
{
    /**
     * Outputs information about the provided variables and ends execution of 
     * the script.
     *
     * This method accepts variable-length arguments and outputs information 
     * about each argument, including a backtrace detailing where the method was 
     * called from.
     *
     * @param   mixed   ...$args    Variable-length list of arguments to output 
     *                              information about.
     * @return  void
     */
    public function dd(...$args)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        unset($backtrace[0]);

        foreach ($backtrace as $index => &$value) {

            if (is_array($value)) {
                self::cleanBacktraceArray($value);
            }

            if (is_object($value)) {
                $value = self::castObjectToArray($value);
            }

            $value[$value['function'] . ' => ' . ($value['file'] ?? '') . '#L:' . ($value['line'] ?? '')] = [
                'args' => $value['args']
            ];

            unset($value['function'], $value['file'], $value['line'], $value['args']);
        }

        $backtrace = array_values($backtrace);

        exit(response($backtrace, 200, \Aeros\Src\Classes\Response::JSON));
    }

    /**
     * Converts an object into an associative array.
     *
     * @param   object  $object     The object to convert into an array.
     * @return  array               The associative array representing the 
     *                              object's properties.
     */
    public static function castObjectToArray(object $object)
    {
        $newObjectContent = [];
        $classname = get_class($object);

        foreach ((array) $object as $objIndex => $objValue) {
            $newIndex = str_replace($classname, '', $objIndex);
            $newObjectContent[strip_tags($newIndex)] = $objValue;
        }

        return $newObjectContent;
    }

    /**
     * Recursively cleans up a backtrace array, converting objects to 
     * associative arrays.
     *
     * This method iterates through the provided backtrace array, cleaning up 
     * any nested arrays and converting any encountered objects into 
     * associative arrays.
     *
     * @param   array   $backtrace  Reference to the backtrace array to clean up.
     * @return  void
     */
    public static function cleanBacktraceArray(array &$backtrace)
    {
        foreach ($backtrace as $key => &$value) {

            if (is_array($value)) {
                self::cleanBacktraceArray($value);
            }

            if (is_object($value)) {

                unset($backtrace[$key]);

                $backtrace[$key] = [
                    'class'  => get_class($value),
                    'object' => self::castObjectToArray($value)
                ];
            }
        }
    }
}
