<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\WRCService;
use Exception;

class DocumentQueryItemResolver implements QueryItemResolverInterface
{
    private WRCService $wrcService;

    public function __construct(WRCService $wrcService){
        $this->wrcService = $wrcService;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __invoke($item, array $context)
    {
        if (isset($input['aanbiederEmployeeDocumentId']) && isset($context['info']->variableValues['studentDocumentId'])) {
            throw new Exception('Both studentDocumentId and aanbiederEmployeeDocumentId are given, please give one type of id');
        }
        if (isset($input['aanbiederEmployeeDocumentId'])) {
            $id = $context['info']->variableValues['aanbiederEmployeeDocumentId'];
            if (strpos($id, '/') !== false) {
                $idArray = explode('/', $id);
                $id = end($idArray);
            }
        } elseif (isset($input['studentDocumentId'])) {
            $id = $context['info']->variableValues['studentDocumentId'];
            if (strpos($id, '/') !== false) {
                $idArray = explode('/', $id);
                $id = end($idArray);
            }
        } else {
            throw new Exception('Both studentDocumentId and aanbiederEmployeeDocumentId are not given, please give one of these');
        }

        return $this->wrcService->getDocument($id);
    }
}
