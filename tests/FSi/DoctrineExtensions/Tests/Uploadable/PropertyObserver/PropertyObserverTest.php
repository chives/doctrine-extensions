<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\PropertyObserver;

use FSi\DoctrineExtensions\Reflection\ObjectReflection;
use FSi\DoctrineExtensions\Tests\Uploadable\PropertyObserver\TestObject;
use FSi\DoctrineExtensions\Uploadable\PropertyObserver\PropertyObserver;
use PHPUnit_Framework_TestCase;

class PropertyObserverTest extends PHPUnit_Framework_TestCase
{
    public function testValueChanged()
    {
        $observer = new PropertyObserver();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $reflection = new ObjectReflection($object);
        $observer->saveValue($reflection, 'property1');
        $observer->saveValue($reflection, 'property2');
        $observer->saveValue($reflection, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasSavedValue($object, 'property1'));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object, 'property2'));
        $this->assertFalse($observer->hasChangedValue($reflection, 'property2'));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property3'));
        $this->assertTrue($observer->hasSavedValue($object, 'property3'));
        $this->assertFalse($observer->hasSavedValue($object, 'property4'));
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException'
        );
        $observer->hasChangedValue($reflection, 'property4');
    }

    public function testChangedValue()
    {
        $observer = new PropertyObserver();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $reflection = new ObjectReflection($object);
        $observer->saveValue($reflection, 'property1');
        $observer->saveValue($reflection, 'property2');
        $observer->saveValue($reflection, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasSavedValue($object, 'property1'));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object, 'property2'));
        $this->assertFalse($observer->hasChangedValue($reflection, 'property2'));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property3'));
        $this->assertTrue($observer->hasSavedValue($object, 'property3'));
        $this->assertFalse($observer->hasSavedValue($object, 'property4'));
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException'
        );
        $observer->hasChangedValue($reflection, 'property4');
    }

    public function testSetValue()
    {
        $observer = new PropertyObserver();

        $object = new TestObject();
        $reflection = new ObjectReflection($object);
        $observer->setValue($reflection, 'property1', 'original value 1');
        $observer->setValue($reflection, 'property2', 'original value 2');
        $observer->setValue($reflection, 'property3', 'original value 3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasChangedValue($reflection, 'property1'));
        $this->assertFalse($observer->hasChangedValue($reflection, 'property2'));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property3'));
        $this->setExpectedException('FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException');
        $observer->hasChangedValue($reflection, 'property4');
    }

    public function testGetSavedValue()
    {
        $observer = new PropertyObserver();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';
        $reflection = new ObjectReflection($object);

        $observer->saveValue($reflection, 'property1');
        $observer->saveValue($reflection, 'property2');
        $observer->saveValue($reflection, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertEquals(
            $observer->getSavedValue($object, 'property1'),
            'original value 1'
        );
        $this->assertEquals(
            $observer->getSavedValue($object, 'property2'),
            'original value 2'
        );
        $this->assertNull($observer->getSavedValue($object, 'property3'));
        $this->setExpectedException(
            'FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException'
        );
        $observer->getSavedValue($object, 'property4');
    }

    public function testTreatNotSavedAsNull()
    {
        $observer = new PropertyObserver();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';
        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';

        $reflection = new ObjectReflection($object);
        $this->assertTrue($observer->hasChangedValue($reflection, 'property1', true));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property2', true));
        $this->assertTrue($observer->hasChangedValue($reflection, 'property3', true));
        $this->assertFalse($observer->hasChangedValue($reflection, 'property4', true));
    }

    public function testRemoveObject()
    {
        $observer = new PropertyObserver();

        $object1 = new TestObject();
        $object1->property1 = 'original value 1';
        $reflection1 = new ObjectReflection($object1);
        $object2 = new TestObject();
        $object2->property2 = 'original value 2';
        $reflection2 = new ObjectReflection($object2);
        $observer->saveValue($reflection1, 'property1');
        $observer->saveValue($reflection2, 'property2');

        $this->assertTrue($observer->hasSavedValue($object1, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object2, 'property2'));

        $observer->remove($object1);

        $this->assertFalse($observer->hasSavedValue($object1, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object2, 'property2'));
    }

    public function testClear()
    {
        $observer = new PropertyObserver();

        $object1 = new TestObject();
        $object1->property1 = 'original value 1';
        $reflection1 = new ObjectReflection($object1);
        $object2 = new TestObject();
        $object2->property2 = 'original value 2';
        $reflection2 = new ObjectReflection($object2);
        $observer->saveValue($reflection1, 'property1');
        $observer->saveValue($reflection2, 'property2');

        $this->assertTrue($observer->hasSavedValue($object1, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object2, 'property2'));

        $observer->clear();

        $this->assertFalse($observer->hasSavedValue($object1, 'property1'));
        $this->assertFalse($observer->hasSavedValue($object2, 'property2'));
    }
}

class TestObject
{
    public $property1;

    public $property2;

    public $property3;

    public $property4;
}
