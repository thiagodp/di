<?php
namespace phputil\di;

use \ReflectionClass;

/**
 *  Configuration of a dependency injection.
 *  
 *  @author	Thiago Delgado Pinto
 */
class Cfg {

	public $name;
	public $clazz = null;
	public $shared = false;
	public $params = array();
	public $callable = null;
	//public $target = null;
	
	function __construct( $name ) { $this->name = $name; }
	
	function create( $clazz ) { $this->clazz = $clazz; return $this; }
	
	function shared( $value = true ) { $this->shared = $value; return $this; }
	
	function receive( $params ) {
		$this->params = is_array( $params ) ? $params : array( $params );
		return $this;
	}
	
	function call( $callable ) { $this->callable = $callable; return $this; }
	
	//function onlyFor( $targetClass ) { $this->onlyFor = $targetClass; return $this; }
}

/**
 *  Dependency Injection
 *  
 *  @author	Thiago Delgado Pinto
 */
class DI {

	// Classes configurations
	private static $configs = array(); // string (class name) => Cfg
	// Shared objects
	private static $objects = array(); // string (class name) => object
	// Arguments references
	private static $args = array(); // string (class name) => array( 'rc' => ReflectionClass, 'params' => constructor params )
	
	private function __construct() {}
	
	static function reset() {
		self::$configs = array();
		self::$objects = array();
	}
	
	static function config( Cfg $cfg ) {
		$className = $cfg->name;
		$name = ltrim( $className, '\\' );
		//self::$configs[ $cfg->name ] = $cfg;
		self::$configs[ $name ] = $cfg;
	}
	
	static function let( $name ) {
		return new Cfg( $name );
	}
	
	/**
	 *  Creates an object of the informed class by calling its
	 *  constructor, or the configured creation function. The 
	 *  constructor is called whether no creation function is 
	 *  configured or $ignoreCallable is true. The given 
	 *  construction parameters should be the same as the
	 *  chosen construction alternative.
	 *  
	 *  @param string $name		Name of the class or interface.
	 *  @param array $params	Construction parameters.
	 *  @return object
	 */	
	static function create(
		$name
		, array $params = array()
		, $ignoreCallable = false
		) {
		//echo 'class is ', $name, "<br />"; var_dump( self::$configs );
		$cName = ltrim( $name, '\\' );
		// A shared object exists?
		if ( isset( self::$objects[ $cName ] ) ) {
			// Return it
			return self::$objects[ $cName ];
		}		

		$hasParams = count( $params ) > 0;

		$hasCfg = isset( self::$configs[ $cName ] );
		$cfg = $hasCfg ? self::$configs[ $cName ] : null; 
		$isShared = $hasCfg && $cfg->shared === true;
		$hasClazz = $hasCfg && $cfg->clazz !== null;
		$hasCallable = $hasCfg && $cfg->callable !== null;

		// Need to use the callable?		
		if ( $hasCallable && ! $ignoreCallable ) {
			// Use it
			$obj = call_user_func_array( $cfg->callable, $params );
		} else {
			$clazzName = $hasClazz ? $cfg->clazz : $name;
			$clazzArgs = null;
			// Reflection information already exists?
			if ( isset( self::$args[ $clazzName ] ) ) {
				// Use it
				$clazzArgs = self::$args[ $clazzName ];
			} else {
				$clazzArgs = array( 'rc' => new ReflectionClass( $clazzName ) );
				self::$args[ $clazzName ] = & $clazzArgs;
			}
			$obj = self::createWithArgs( $clazzArgs, $clazzName, $params, $cfg );
		}
		
		// If is shared, add to the shared objects
		if ( $isShared ) { self::$objects[ $name ] = $obj; }
		
		return $obj;
	}


	private static function createWithArgs( &$clazzArgs, $clazzName, array &$params, &$cfg ) {

		$rc = $clazzArgs[ 'rc' ];
		$isInstantiable = $rc->isInstantiable() === true;

		$rParams = null;
		if ( isset( $clazzArgs[ 'params' ] ) ) {
			$rParams = $clazzArgs[ 'params' ];
		} else {
			$cParams = $rc->getConstructor() !== null
				? $rc->getConstructor()->getParameters()
				: array();

			$rParams = array();
			foreach ( $cParams as $rp ) {
				$newParam = array(
					'name' => $rp->getName(),
					'class' => $rp->getClass() ? $rp->getClass()->name : null
				);
				if ( $rp->isDefaultValueAvailable() ) {
					$newParam[ 'defValue' ] = $rp->getDefaultValue();
				}
				$rParams []= $newParam;
			}

			$clazzArgs[ 'params' ] = & $rParams;
		}		
		// Can instantiate directly ?
		if ( $isInstantiable && count( $rParams ) < 1 ) {
			return new $clazzName;
		}
		
		// Let's build $args with the arguments for the constructor
		$args = array();

		foreach ( $rParams as $i => $p ) {
			
			$key = $p[ 'name' ];
			$args[ $key ] = null;
			
			if ( isset( $cfg->params[ $i ] ) ) {
				$args[ $key ] = $cfg->params[ $i ];
			} else if ( isset( $params[ $i ] ) ) {
				$args[ $key ] = $params[ $i ];
			} else if ( array_key_exists( 'defValue', $p ) ) {
				$args[ $key ] = $p[ 'defValue' ];
			} else {
				$rpClass = $p[ 'class' ];
				if ( $rpClass !== null ) {
					$args[ $key ] = self::create( $rpClass );
				}
			}
		}		
		//echo "\n", 'className is '; var_dump( $clazzName );
		//echo "\n", 'configs is '; var_dump( self::$configs );
		//echo "\n", 'args is '; var_dump( $args );
		//echo "\n", 'rParams is '; var_dump( $rParams );

		// Create the class with the built arguments
		return $rc->newInstanceArgs( $args );
	}
}
?>