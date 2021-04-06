<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Example;
use App\Entity\Example as MyExample;

class ExampleTest extends TestCase
{
    public function testSomething()
    {
        // Let create a new Example resource
        $example = New MyExample;
        // Set a name
        $example->setName('My test name');

        // Test if the name has been set
        $this->assertEquals('My test name',$example->getName());

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
