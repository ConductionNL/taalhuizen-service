<?php

namespace App\Service;

use App\Entity\Availability;
use App\Entity\AvailabilityDay;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;

    /**
     * EAVService constructor.
     *
     * @param LayerService $layerService
     */
    public function __construct(LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
    }

    /**
     * @param array $availabilityResult
     *
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
     *
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

    /**
     * @param array $newAvailabilityMemo An array containing the info for a new memo (description(the memo), topic & author)
     *
     * @return array
     */
    public function saveAvailabilityMemo(array $newAvailabilityMemo): array
    {
        $availabilityMemo = ['name' => 'Availability notes'];
        $query = [
            'name'   => 'Availability notes',
            'topic'  => $newAvailabilityMemo['topic'],
            'author' => $newAvailabilityMemo['author'] ?? $newAvailabilityMemo['topic'],
        ];
        $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], $query)['hydra:member'];
        if (count($availabilityMemos) > 0) {
            $availabilityMemo = $availabilityMemos[0];
        }
        $availabilityMemo = array_merge($availabilityMemo, $newAvailabilityMemo);
        if (!isset($availabilityMemo['author'])) {
            $availabilityMemo['author'] = $newAvailabilityMemo['topic'];
        }
        if (!isset($availabilityMemo['description'])) {
            $availabilityMemo['description'] = $availabilityMemo['name'];
        }

        return $this->commonGroundService->saveResource($availabilityMemo, ['component' => 'memo', 'type' => 'memos']);
    }

    /**
     * @param string      $topic  The topic of a memo
     * @param string|null $author The author of a memo
     *
     * @return array
     */
    public function getAvailabilityMemo(string $topic, string $author = null): array
    {
        $query = [
            'name'   => 'Availability notes',
            'topic'  => $topic,
            'author' => $author ?? $topic,
        ];
        $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], $query)['hydra:member'];
        if (count($availabilityMemos) > 0) {
            return $availabilityMemos[0];
        }

        return [];
    }
}
