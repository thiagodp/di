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
		self::$configs[ $cfg->name ] = $cfg;
	}
	
	static function let( $name ) {
		return new Cfg( $name );
	}
	
	static function create( $name, array $params = array() ) {
		
		$hasParams = count( $params ) > 0;
		//echo 'class is ', $name, "\n";
		
		$hasCfg = isset( self::$configs[ $name ] );
		$cfg = $hasCfg ? self::$configs[ $name ] : null; 
		
		$hasClazz = $hasCfg && $cfg->clazz !== null;
		$isShared = $hasCfg && $cfg->shared === true;
		$hasCallable = $hasCfg && $cfg->callable !== null;
		
		if ( $isShared && isset( self::$objects[ $name ] ) ) {
			return self::$objects[ $name ];
		}
		
		if ( $hasCallable ) {
			$obj = call_user_func( $cfg->callable );
			if ( $isShared ) { self::$objects[ $name ] = $obj; }
			return $obj;
		}
		
		$rc = new \ReflectionClass( $hasClazz ? $cfg->clazz : $name );
		$hasConstructor = $rc->getConstructor() !== null;
		$rParams = $hasConstructor ? $rc->getConstructor()->getParameters() : array();
		
		if ( ! $hasCfg && count( $rParams ) < 1 ) {
			return new $name;
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
		
		//echo "\n", 'args is '; var_dump( $args );
		
		$obj = $rc->newInstanceArgs( $args );
		if ( $isShared ) { self::$objects[ $name ] = $obj; }
		
		return $obj;
	}
}
?>