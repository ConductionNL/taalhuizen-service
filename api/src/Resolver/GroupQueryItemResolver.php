<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\Group;
use App\Service\EDUService;
use App\Service\StudentService;
use Doctrine\Common\Collections\ArrayCollection;
use phpDocumentor\Reflection\Types\This;

class GroupQueryItemResolver implements QueryItemResolverInterface
{
    private EDUService $eduService;
    private StudentService $studentService;

    public function __construct(EDUService $eduService){
        $this->eduService = $eduService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        $groupId = explode('/',$context['info']->variableValues['groupId']);
        if (is_array($groupId)) {
            $groupId = end($groupId);
        }
        return $this->eduService->getGroup($groupId);
    }

}
