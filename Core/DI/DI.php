<?php
namespace Core\DI;

/**
 * DI.php
 *
 * @author Michael "Tekkla" Zorn <tekkla@tekkla.de>
 * @copyright 2016-2017
 */
class DI implements DIInterface
{

    /**
     *
     * @var array
     */
    private $settings = [];

    /**
     * Mapped services, factories and values
     *
     * @var array
     */
    private $map = [];

    /**
     * Singleton service storage
     *
     * @var array
     */
    private $names = [];

    /**
     *
     * @var DI
     */
    private static $instance = false;

    /**
     * Creates and returns singleton instance of DI service
     *
     * @param array $settings
     *            Optional settings array which is only needed once on instance creation
     *            
     * @return DIInterface
     */
    public static function getInstance(array $settings = []): DIInterface
    {
        if (! self::$instance) {
            self::$instance = new DI($settings);
        }
        
        return self::$instance;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::instance($class_name, $arguments)
     */
    public function instance(string $class_name, $arguments = null)
    {
        // Initialized the ReflectionClass
        Try {
            $reflection = new \ReflectionClass($class_name);
        } catch (\Throwable $t) {
            Throw new DIException($t->getMessage());
        }
        
        // Creating an instance of the class when no arguments provided
        if (empty($arguments) || count($arguments) == 0) {
            $obj = new $class_name();
        } // Creating instance of class with provided arguments
else {
            
            if (! is_array($arguments)) {
                $arguments = (array) $arguments;
            }
            
            // Replace text arguments with objects
            foreach ($arguments as $key => $arg) {
                
                if (is_array($arg)) {
                    
                    $options = [];
                    
                    foreach ($arg as $arr_arg) {
                        
                        list ($arg_key, $di_service) = explode('::', $arr_arg);
                        
                        if (strpos($di_service, '.') === false) {
                            continue;
                        }
                        
                        $options[$arg_key] = $this->get($di_service);
                    }
                    
                    $arguments[$key] = $options;
                    
                    continue;
                }
                
                // Skip strings without di container typical dot
                if (is_object($arg) || strpos($arg, '.') === false) {
                    continue;
                }
                
                $arguments[$key] = $this->get($arg);
            }
            
            $obj = $reflection->newInstanceArgs($arguments);
        }
        
        // if (! property_exists($obj, 'di')) {
        $obj->di = $this;
        // }
        
        // Inject and return the created instance
        return $obj;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::mapValue($name, $value)
     */
    public function mapValue(string $name, $value)
    {
        $this->map[$name] = [
            'value' => $value,
            'type' => 'value'
        ];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::mapService($name, $value, $arguments)
     */
    public function mapService(string $name, string $value, $arguments = null)
    {
        $this->map[$name] = [
            'value' => $value,
            'type' => 'service',
            'arguments' => $arguments
        ];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::mapFactory($name, $value, $arguments)
     */
    public function mapFactory(string $name, string $value, $arguments = null)
    {
        $this->map[$name] = [
            'value' => $value,
            'type' => 'factory',
            'arguments' => $arguments
        ];
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::invokeMethod($obj, $method, $params)
     */
    public function invokeMethod(&$obj, string $method, array $params = [])
    {
        if (! is_array($params)) {
            Throw new DIException('Parameter to invoke needs to be of type array.');
        }
        
        // Look for the method in object. Throw error when missing.
        if (! method_exists($obj, $method)) {
            Throw new DIException(sprintf('Method "%s" not found in "%s".', $method, get_class($obj)));
        }
        
        // Get reflection method
        $method = new \ReflectionMethod($obj, $method);
        
        // Init empty arguments array
        $args = [];
        
        // Get list of parameters from reflection method object
        $method_parameter = $method->getParameters();
        
        // Let's see what arguments are needed and which are optional
        foreach ($method_parameter as $parameter) {
            
            // Get current paramobject name
            $param_name = $parameter->getName();
            
            // Parameter is not optional and not set => throw error
            if (! $parameter->isOptional() && ! isset($params[$param_name])) {
                Throw new DIException(sprintf('Not optional parameter "%s" missing', $param_name));
            }
            
            // If parameter is optional and not set, set argument to null
            $args[] = $parameter->isOptional() && ! isset($params[$param_name]) ? $parameter->getDefaultValue() : $params[$param_name];
        }
        
        // Return result executed method
        return $method->invokeArgs($obj, $args);
    }

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
    private function getSFV(string $name)
    {
        if (! $this->offsetExists($name)) {
            Throw new DIException(sprintf('Service, factory or value "%s" is not mapped.', $name));
        }
        
        $type = $this->map[$name]['type'];
        $value = $this->map[$name]['value'];
        
        if ($type == 'value') {
            return $value;
        } elseif ($type == 'factory') {
            return $this->instance($value, $this->map[$name]['arguments']);
        } else {
            
            if (! isset($this->services[$name])) {
                $this->services[$name] = $this->instance($value, $this->map[$name]['arguments']);
            }
            
            return $this->services[$name];
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::exists()
     */
    public function exists(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Core\DI\DIInterface::get()
     */
    public function get(string $name)
    {
        return $this->getSFV($name);
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($name)
    {
        return array_key_exists($name, $this->map);
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($name)
    {
        return $this->getSFV($name);
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \ArrayAccess::offsetSet()
     *
     * @throws DIException
     */
    public function offsetSet($name, $value)
    {
        // No mapping through this way.
        Throw new DIException('It is not allowed to map services, factories or values this way. Use the specific map methods instead.');
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($name)
    {
        if ($this->offsetExists($name)) {
            unset($this->map[$name]);
        }
    }
}
