<?php 

	use PHPUnit\Framework\TestCase;

	class BaseStaticClassTest extends TestCase
	{	
	
		public static function setUpBeforeClass()
		{
		
		}
		
		public static function tearDownAfterClass()
		{
			
		}
		
		protected function setUp()
		{
		}

		protected function tearDown()
		{
		}
		
		public function testNotYet()
		{
			$this->markTestSkipped();
		}

	}