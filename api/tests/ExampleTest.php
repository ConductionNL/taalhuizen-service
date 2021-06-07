<?php

namespace App\Tests;

use App\Entity\Example as MyExample;
use PHPUnit\Framework\Example;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testSomething()
    {
        // Let create a new Example resource
        $example = new MyExample();
        // Set a name
        $example->setName('My test name');

        // Test if the name has been set
        $this->assertEquals('My test name', $example->getName());

        /*
        // Lets test if the example can be saved to the database
        $this->assertTrue($em->persist($example));
        $this->assertTrue($em->flush);

        // Lets test if the example has an ID (or has been succefully saved to the database
        $this->assertTrue($example->getId());

        // Let delete the example to keep de database clean
        $this->assertTrue($em->remove($example));
        $this->assertTrue($em->flush);
        */
    }
}
