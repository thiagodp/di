<?php
namespace phputil\di;

/**
 *  Configuration of a class.
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

	private static $configs = array();
	private static $objects = array();
	//private static $targets = array();
	
	private function __construct() {}
	
	static function reset() {
		self::$configs = array();
		self::$objects = array();
		//self::$targets = array();
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
		
		$hasParams = count( $params ) > 0;
		//echo 'class is ', $name, "<br />"; var_dump( self::$configs );
		
		$cName = ltrim( $name, '\\' );
		$hasCfg = isset( self::$configs[ $cName ] );
		$cfg = $hasCfg ? self::$configs[ $cName ] : null; 
		
		$hasClazz = $hasCfg && $cfg->clazz !== null;
		//echo 'hasClazz is ', $hasClazz ? 'true' : 'false', '<br />';
		$isShared = $hasCfg && $cfg->shared === true;
		$hasCallable = $hasCfg && $cfg->callable !== null;
		
		if ( $isShared && isset( self::$objects[ $cName ] ) ) {
			return self::$objects[ $cName ];
		}
		
		if ( $hasCallable && ! $ignoreCallable ) {
			$obj = call_user_func_array( $cfg->callable, $params );
			if ( $isShared ) { self::$objects[ $name ] = $obj; }
			return $obj;
		}
		
		$clazzName = $hasClazz ? $cfg->clazz : $name;
		$rc = new \ReflectionClass( $clazzName );
		$isInstantiable = $rc->isInstantiable() === true;
		$hasConstructor = $rc->getConstructor() !== null;
		$rParams = $hasConstructor ? $rc->getConstructor()->getParameters() : array();
		
		if ( $isInstantiable && count( $rParams ) < 1 ) {
			return new $clazzName;
		}
		
		$args = array();
		$i = 0;
		foreach ( $rParams as $rp ) {
			
			$key = $rp->getName();
			$args[ $key ] = null;
			
			if ( $hasParams && isset( $params[ $i ] ) ) {
				$args[ $key ] = $params[ $i ];
			} else if ( $rp->isDefaultValueAvailable() ) {
				$args[ $key ] = $rp->getDefaultValue();
			} else {
				$rpClass = $rp->getClass() ? $rp->getClass()->name : null;
				if ( $rpClass !== null ) {
					$args[ $key ] = self::create( $rpClass );
				}
			}
			// Overwrite with configured values, if are
			if ( isset( $cfg->params[ $i ] ) ) {
				$args[ $key ] = $cfg->params[ $i ];
			}
			++$i;
		}
		
		//echo "\n", 'className is '; var_dump( $clazzName );
		//echo "\n", 'configs is '; var_dump( self::$configs );
		//echo "\n", 'args is '; var_dump( $args );
		//echo "\n", 'rParams is '; var_dump( $rParams );
		$obj = $rc->newInstanceArgs( $args );
		if ( $isShared ) { self::$objects[ $name ] = $obj; }
		
		return $obj;
	}
}
?>