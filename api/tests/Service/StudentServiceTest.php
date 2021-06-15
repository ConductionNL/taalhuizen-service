<?php

namespace App\Tests\Service;

use App\Service\StudentService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StudentServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $studentService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->studentService = $this->serviceContainer->get(StudentService::class);
        parent::setUp();
    }

    public function testGetStudents()
    {
    }

    public function testGetEducationsFromEmployee()
    {
    }

    public function testHandleResult()
    {
    }

    public function testGetStudent()
    {
    }

    public function testGetStudentsWithStatus()
    {
    }

    public function testCheckStudentValues()
    {
    }
}
