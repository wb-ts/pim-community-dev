<?php
declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Controller\InternalApi;

use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\PimDataGridBundle\Adapter\GridFilterAdapterInterface;
use Oro\Bundle\PimDataGridBundle\Normalizer\IdEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sequential edit controller
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SequentialEditController
{
    private const MAX_PRODUCT_COUNT = 1000;

    /** @var MassActionParametersParser */
    protected $parameterParser;

    /** @var GridFilterAdapterInterface */
    protected $filterAdapter;

    /** @var ProductQueryBuilderFactoryInterface */
    protected $pqbFactory;

    protected UserContext $userContext;

    /**
     * @param ProductQueryBuilderFactoryInterface $pqbFactory
     */
    public function __construct(
        MassActionParametersParser          $parameterParser,
        GridFilterAdapterInterface          $filterAdapter,
        ProductQueryBuilderFactoryInterface $pqbFactory,
        UserContext                         $userContext
    ) {
        $this->parameterParser = $parameterParser;
        $this->filterAdapter = $filterAdapter;
        $this->pqbFactory = $pqbFactory;
        $this->userContext = $userContext;
    }

    /**
     * Get ids from datagrid request
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getIdsAction(Request $request): JsonResponse
    {
        $parameters = $this->parameterParser->parse($request);
        $filters = $this->filterAdapter->adapt($parameters);

        $products = [];

        $cursor = $this->getProductsCursor(
            $filters,
            $this->initEmptyParameterWithDefault($parameters)
        );

        while ($cursor->valid() && $cursor->key() < self::MAX_PRODUCT_COUNT) {
            $products[] = IdEncoder::decode($cursor->current());
            $cursor->next();
        }

        return new JsonResponse(['entities' => $products, 'total' => $cursor->count()]);
    }

    /**
     * @param array $filters
     * @param array $context
     *
     * @return CursorInterface
     */
    protected function getProductsCursor(array $filters, array $context): CursorInterface
    {
        $productQueryBuilder = $this->pqbFactory->create(['filters' => $filters]);
        if (null !== $context['sort']) {
            $field = key($context['sort']);
            $productQueryBuilder->addSorter($field, $context['sort'][$field], $context);
        }

        return $productQueryBuilder->execute();
    }

    protected function initEmptyParameterWithDefault(array $parameters): array
    {
        return [
            'locale' => $parameters['dataLocale']
                ?: $this->userContext->getCurrentLocaleCode(),
            'scope' => isset($parameters['dataScope']) ?
                $parameters['dataScope']['value']
                : $this->userContext->getUserChannelCode(),
            'sort' => $parameters['sort']
        ];
    }
}
