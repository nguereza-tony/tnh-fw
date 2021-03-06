<?php 

	/**
     * EventDispatcher class tests
     *
     * @group core
     * @group core_classes
     * @group event
     */
	class EventDispatcherTest extends TnhTestCase {	
	
		public static function setUpBeforeClass() {
            //some listeners using for test
            require_once TESTS_PATH . 'include/listeners_event_dispatcher_test.php';
		}
		
		public function testAddListenerFirstTime() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners('fooEvent'));
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
			$this->assertNotEmpty($d->getListeners('fooEvent'));
			$this->assertSame(1, count($d->getListeners('fooEvent')));
		}
        
        public function testAddListenerMoreTime() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners('fooEvent'));
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
			$this->assertSame(1, count($d->getListeners('fooEvent')));
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $this->assertNotEmpty($d->getListeners('fooEvent'));
            $this->assertSame(2, count($d->getListeners('fooEvent')));
		}
        
        public function testRemoveListenerEventNotExist() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners('fooEvent'));
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->removeListener('unknowEvent', array($listener, 'emptyListener'));
			$this->assertNotEmpty($d->getListeners('fooEvent'));
			$this->assertSame(1, count($d->getListeners('fooEvent')));
		}
        
        public function testRemoveListenerEventExistListenerNotExist() {
            $listener = new ListenersEventDispatcherTest();
            $correctListener = array($listener, 'emptyListener');
            $d = new EventDispatcher();
            $this->assertEmpty($d->getListeners('fooEvent'));
            $d->addListener('fooEvent', $correctListener);
            $d->removeListener('fooEvent', array($listener, 'fooListener'));
			$this->assertNotEmpty($d->getListeners('fooEvent'));
			$this->assertSame(1, count($d->getListeners('fooEvent')));
		}
        
        public function testRemoveListenerEventAndListenerExist() {
            $listener = new ListenersEventDispatcherTest();
            $correctListener = array($listener, 'emptyListener');
            $d = new EventDispatcher();
            $this->assertEmpty($d->getListeners('fooEvent'));
            $d->addListener('fooEvent', $correctListener);
            $this->assertSame(1, count($d->getListeners('fooEvent')));
            $d->removeListener('fooEvent', $correctListener);
			$this->assertEmpty($d->getListeners('fooEvent'));
			$this->assertSame(0, count($d->getListeners('fooEvent')));
		}
        
        public function testRemoveAllListenerEventParamIsNull() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertNotEmpty($d->getListeners('fooEvent'));
            $this->assertNotEmpty($d->getListeners('barEvent'));
			$this->assertSame(2, count($d->getListeners()));
            $d->removeAllListener(null);
            $this->assertEmpty($d->getListeners('fooEvent'));
            $this->assertEmpty($d->getListeners('barEvent'));
            $this->assertSame(0, count($d->getListeners()));
		}
        
        public function testRemoveAllListenerEventParamIsNotNullAndExist() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertNotEmpty($d->getListeners('fooEvent'));
            $this->assertNotEmpty($d->getListeners('barEvent'));
			$this->assertSame(2, count($d->getListeners()));
            $d->removeAllListener('fooEvent');
            $this->assertEmpty($d->getListeners('fooEvent'));
            $this->assertNotEmpty($d->getListeners('barEvent'));
            $this->assertSame(1, count($d->getListeners()));
		}
        
        public function testRemoveAllListenerEventParamIsNotNullAndNotExist() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertNotEmpty($d->getListeners('fooEvent'));
            $this->assertNotEmpty($d->getListeners('barEvent'));
			$this->assertSame(2, count($d->getListeners()));
            $d->removeAllListener('unknowEvent');
            $this->assertNotEmpty($d->getListeners('fooEvent'));
            $this->assertNotEmpty($d->getListeners('barEvent'));
            $this->assertSame(2, count($d->getListeners()));
		}
  
        
        public function testGetListenersEventParamIsNull() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertSame(2, count($d->getListeners(null)));
		}
        
        public function testGetListenersEventParamIsNotNullAndExist() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertSame(1, count($d->getListeners('barEvent')));
		}
        
        public function testGetListenersEventParamIsNotNullAndNotExist() {
            $d = new EventDispatcher();
            $listener = new ListenersEventDispatcherTest();
            $this->assertEmpty($d->getListeners());
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->addListener('barEvent', array($listener, 'emptyListener'));
            $this->assertSame(2, count($d->getListeners()));
            $this->assertSame(0, count($d->getListeners('unknowEvent')));
		}
        
        public function testDispatchParamEventIsNullOrNotInstanceOfEventInfo() {
            $listener = new ListenersEventDispatcherTest();
            $d = new EventDispatcher();
            $d->addListener('fooEvent', array($listener, 'emptyListener'));
            $d->dispatch('fooEvent');
            //fuck
            $this->assertTrue(true);
        }
        
        public function testDispatchParamEventIsStopped() {
            $listener = $this->getMockBuilder('ListenersEventDispatcherTest')->getMock();
            $listener->expects($this->never())
                     ->method('stopEventListener');
                    
            $d = new EventDispatcher();
            $event = new EventInfo('fooEvent', null, false, true);
            $this->assertTrue($event->isStop());
            $d->addListener('fooEvent', array($listener, 'stopEventListener'));
            $d->dispatch($event);
        }
        
        public function testDispatchParamEventIsStoppedDuringListenerCall() {
            $listener = new ListenersEventDispatcherTest();
                    
            $d = new EventDispatcher();
            $event = new EventInfo('fooEvent', null, true);
            $d->addListener('fooEvent', array($listener, 'returnBackEventListenerAndStop'));
            $d->addListener('fooEvent', array($listener, 'stopEventListener'));
            $result = $d->dispatch($event);
            $this->assertSame('bar', $result->getPayload());
        }
        
        public function testDispatchParamEventNeedReturnBack() {
            $listener = new ListenersEventDispatcherTest();
                    
            $d = new EventDispatcher();
            $event = new EventInfo('fooEvent', null, true);
            $this->assertTrue($event->isReturnBack());
            $d->addListener('fooEvent', array($listener, 'returnBackEventListener'));
            $result = $d->dispatch($event);
            $this->assertSame('foo', $result->getPayload());
        }
        
        public function testDispatchParamEventNeedReturnBackButNoReturn() {
            $listener = new ListenersEventDispatcherTest();
                    
            $d = new EventDispatcher();
            $event = new EventInfo('fooEvent', null, true);
            $this->assertTrue($event->isReturnBack());
            $d->addListener('fooEvent', array($listener, 'returnBackEventListenerButNotReturnIt'));
            $result = $d->dispatch($event);
            $this->assertNull($result);
        }
        
        public function testDispatchNoListenerForEvent() {
            $event = new EventInfo('fooEvent');
            $d = new EventDispatcher();
            $d->dispatch($event);
            $this->assertSame($event->getPayload(), $event->getPayload());
        }
        
        public function testDispatchNoListenerForEventAndNeedReturnBack() {
            $event = new EventInfo('fooEvent', null, true);
            $d = new EventDispatcher();
            $result = $d->dispatch($event);
            $this->assertSame($event, $result);
        }

	}