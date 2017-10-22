<?php
namespace Core\DI;

/**
 * DIInterface.php
 *
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @copyright 2017
 * @license MIT
 */
interface DIInterface extends \ArrayAccess
{

    /**
     * Creates an instance of a class
     *
     * Analyzes $arguments parameter and injects needed services and objects
     * into the object instance. A so created object instance gets always the
     * di container object injected.
     *
     * @param string $class_name
     * @param mixed $arguments
     *
     * @return object
     */
    public function instance(string $class_name, $arguments = null);

    /**
     * Maps a named value
     *
     * @param string $name
     *            Name of the value
     * @param mixed $value
     *            The value itself
     */
    public function mapValue(string $name, $value);

    /**
     * Maps a named service
     *
     * Requesting this service will result in returning always the same object.
     *
     * @param string $name
     *            Name of the service
     * @param string $value
     *            Class to use for object creation
     * @param mixed $arguments
     *            Arguments to provide on instance create
     */
    public function mapService(string $name, string $value, $arguments = null);

    /**
     * Maps a class by name
     *
     * Requestingthis class will result in new object.
     *
     * @param string $name
     *            Name to access object
     * @param string $value
     *            Classname of object
     * @param mixed $arguments
     *            Arguments to provide on instance create
     */
    public function mapFactory(string $name, string $value, $arguments = null);

    /**
     * Executes object method by using Reflection
     *
     * @param $obj Object
     *            to call parameter injected method
     * @param string $method
     *            Name
     *            of method to call
     * @param $params (Optional)
     *            Array of parameters to inject into method
     *            
     * @throws DIException
     *
     * @return object
     */
    public function invokeMethod(&$obj, string $method, array $params = []);

    /**
     * Returns the requested SFV (Service/Factory/Value)
     *
     * @param string $name
     *            Name of the Service, Factory or Value to return
     *            
     * @throws DIException
     *
     * @return mixed
     */
    public function getSFV(string $name);

    /**
     * Checks for a registred SFV
     *
     * @param string $name
     *            Name of service, factory or value to check
     *            
     * @return bool
     */
    public function exists(string $name): bool;

    /**
     * Returns requested service, factory or value
     *
     * @param string $name
     *            Name of registered service, class or value
     *            
     * @return object
     */
    public function get(string $name);
}
