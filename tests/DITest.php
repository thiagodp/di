<?php
namespace phputil\di\tests;

use phputil\di\DI;

// SOME CLASSES USED IN THE TESTS _____________________________________________

interface I1 {
	function text();
}

class C1 implements I1 {
	
	public $text;
	
	function __construct( $text = 'hi' ) { $this->text = $text; }
	
	function text() { return $this->text; }
}

class C2 {
	
	private $i1, $value;
	
	function __construct( I1 $i1, $value ) {
		$this->i1 = $i1;
		$this->value = $value;
	}
	
	function i1() { return $this->i1; }
	function value() { return $this->value; }
}

class C3 {
	
	public $i1, $c1, $c2;
	
	function __construct( I1 $i1, C1 $c1, C2 $c2 ) {
		$this->i1 = $i1;
		$this->c1 = $c1;
		$this->c2 = $c2;
	}
}

class C4 {
	public $a, $b, $c, $d, $e, $f, $g;
	
	function __construct( $a, $b, $c, $d, $e, $f, $g ) {
		$this->a = $a;
		$this->b = $b;
		$this->c = $c;
		$this->d = $d;
		$this->e = $e;
		$this->f = $f;
		$this->g = $g;
	}
}

class C5 extends C1 {
	
	public $c3, $c2, $c1, $i1;
	
	function __construct( $text = 'yo', C3 $c3, C2 $c2, C1 $c1, I1 $i1 ) {
		parent::__construct( $text );
		$this->c3 = $c3;
		$this->c2 = $c2;
		$this->c1 = $c1;
		$this->i1 = $i1;
	}
}

// THE TEST ___________________________________________________________________

/**
 *	Tests DI
 *
 *	@author	Thiago Delgado Pinto
 */
class DITest extends \PHPUnit_Framework_TestCase {
	
	const PKG = 'phputil\\di\tests\\';
		
	function setUp() {
		DI::reset();
	}
	
	function test_can_create_interface_with_class() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' ) );
		$o1 = new C1();
		$o2 = DI::create( self::PKG . 'I1' );
		$this->assertEquals( get_class( $o1 ), get_class( $o2 ) );
	}
	
	function test_can_create_interface_with_class_passing_simple_params() {
		$text = 'Hello';
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' )->receive( $text ) );
		$o1 = new C1( $text );
		$o2 = DI::create( self::PKG . 'I1' );
		$this->assertEquals( $o1->text(), $o2->text() );
	}
	
	function test_shared_instance_return_the_same_object() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' )->shared() );
		$o1 = DI::create( self::PKG . 'I1' );
		$o2 = DI::create( self::PKG . 'I1' );
		$this->assertTrue( $o1 === $o2 );
	}
	
	function test_can_create_classes_that_depend_on_interfaces() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' ) );
		$o1 = DI::create( self::PKG . 'C2' );
		$this->assertEquals( self::PKG . 'C1', get_class( $o1->i1() ) );
	}
	
	function test_can_create_classes_that_depend_on_interfaces_and_classes() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' ) );
		$o1 = DI::create( self::PKG . 'C3' );
		$this->assertEquals( self::PKG . 'C1', get_class( $o1->i1 ) );
		$this->assertEquals( self::PKG . 'C1', get_class( $o1->c1 ) );
		$this->assertEquals( self::PKG . 'C2', get_class( $o1->c2 ) );
	}
	
	function createC1() {
		return new C1( 'c1 is here' );
	}
	
	function test_can_create_interface_with_callable_method() {
		DI::config( DI::let( self::PKG . 'I1' )->call( array( $this, 'createC1' ) ) );
		
		$o1 = DI::create( self::PKG . 'I1' );
		$this->assertEquals( self::PKG . 'C1', get_class( $o1 ) );
	}
	
	function test_can_create_class_with_callable_method() {
		DI::config( DI::let( self::PKG . 'C1' )->call( array( $this, 'createC1' ) ) );
		$o1 = DI::create( self::PKG . 'C1' );
		$this->assertEquals( 'c1 is here', $o1->text() );
	}
	
	function test_can_create_class_with_callable_closure() {
		DI::config( DI::let( self::PKG . 'C1' )->call(
			function() { return new C1( 'c1 in closure' ); }
			) );
		$o1 = DI::create( self::PKG . 'C1' );
		$this->assertEquals( 'c1 in closure', $o1->text() );
	}
	
	function test_can_create_shared_class_with_callable_closure() {
		DI::config( DI::let( self::PKG . 'C1' )->shared()->call(
			function() { return new C1( 'c1 in closure' ); }
			) );
		$o1 = DI::create( self::PKG . 'C1' );
		$o2 = DI::create( self::PKG . 'C1' );
		$this->assertEquals( 'c1 in closure', $o1->text() );
		$this->assertEquals( 'c1 in closure', $o2->text() );
		$this->assertTrue( $o1 === $o2 );
	}	
	
	function test_can_create_class_with_little_params() {
		$text = 'World';
		$o1 = DI::create( self::PKG . 'C1', array( $text ) );
		$this->assertEquals( $text, $o1->text() );
	}
	
	function test_can_create_class_with_many_params() {
		$o1 = DI::create( self::PKG . 'C4', array( 1, 2, 3, 4, 5, 6, 7 ) );
		$this->assertEquals( array( 1, 2, 3, 4, 5, 6, 7 ),
			array( $o1->a, $o1->b, $o1->c, $o1->d, $o1->e, $o1->f, $o1->g ) );
	}
	
	function test_can_create_class_with_inheritance() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' ) );
		$o1 = DI::create( self::PKG . 'C5' );
		$this->assertEquals( 'yo', $o1->text() );
	}
	
	/*
	function test_instantiate_interface_differently_depend_on_the_class() {
		DI::config( DI::let( self::PKG . 'I1' )->create( self::PKG . 'C1' )->onlyFor( self::PKG . 'C2' ) );
		DI::config( DI::let( self::PKG . 'I1' )->call( array( $this, 'createC1' ) )->onlyFor( self::PKG . 'C3' ) );
		DI::config( DI::let( self::PKG . 'I1' )->call(
				function() { return new C1( 'bla' ); }
			)->onlyFor( self::PKG . 'C5' ) );
		
		$o1 = DI::create( self::PKG . 'C2' );
		$o2 = DI::create( self::PKG . 'C3' );
		$o3 = DI::create( self::PKG . 'C5' );
		
		$this->assertEquals( 'hi', $o1->i1()->text() );
		$this->assertEquals( 'c1 is here', $o2->i1()->text() );
		$this->assertEquals( 'bla', $o3->i1()->text() );
	}
	*/
}

?>