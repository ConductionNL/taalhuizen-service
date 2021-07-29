<?php

namespace App\Service;

use App\Entity\Availability;
use App\Entity\AvailabilityDay;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class AvailabilityService
{
    private EntityManagerInterface $entityManager;

    /**
     * EAVService constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $availabilityResult
     * @return Availability the saved object.
     */
    public function createAvailabilityObject(array $availabilityResult): Availability
    {
        $availability = new Availability();
        $availability->setMonday($availabilityResult['monday'] ? $this->createAvailabilityDayObject($availabilityResult['monday']) : null);
        $availability->setTuesday($availabilityResult['tuesday'] ? $this->createAvailabilityDayObject($availabilityResult['tuesday']) : null);
        $availability->setWednesday($availabilityResult['wednesday'] ? $this->createAvailabilityDayObject($availabilityResult['wednesday']) : null);
        $availability->setThursday($availabilityResult['thursday'] ? $this->createAvailabilityDayObject($availabilityResult['thursday']) : null);
        $availability->setFriday($availabilityResult['friday'] ? $this->createAvailabilityDayObject($availabilityResult['friday']) : null);
        $availability->setSaturday($availabilityResult['saturday'] ? $this->createAvailabilityDayObject($availabilityResult['saturday']) : null);
        $availability->setSunday($availabilityResult['sunday'] ? $this->createAvailabilityDayObject($availabilityResult['sunday']) : null);

        $this->entityManager->persist($availability);

        return $availability;
    }

    /**
     * @param array $availabilityDayResult
     * @return AvailabilityDay the saved object.
     */
    public function createAvailabilityDayObject(array $availabilityDayResult): AvailabilityDay
    {
        $availabilityDay = new AvailabilityDay();
        $availabilityDay->setMorning($availabilityDayResult['morning']);
        $availabilityDay->setAfternoon($availabilityDayResult['afternoon']);
        $availabilityDay->setEvening($availabilityDayResult['evening']);

        $this->entityManager->persist($availabilityDay);

        return $availabilityDay;
    }
}
