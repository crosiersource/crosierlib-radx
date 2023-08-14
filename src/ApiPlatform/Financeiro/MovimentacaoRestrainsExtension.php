<?php


namespace CrosierSource\CrosierLibRadxBundle\ApiPlatform\Financeiro;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoRestrainsExtension implements ContextAwareQueryCollectionExtensionInterface
{

    public function applyToCollection(QueryBuilder                $queryBuilder,
                                      QueryNameGeneratorInterface $queryNameGenerator,
                                      string                      $resourceClass,
                                      string                      $operationName = null,
                                      array                       $context = [])
    {
        if ($resourceClass === Movimentacao::class && ($context['filters']['carteirasIds'] ?? false)) {
            if (is_string($context['filters']['carteirasIds'])) {
                $carteirasIds = explode(',', $context['filters']['carteirasIds']);
            } elseif (is_array($context['filters']['carteirasIds'])) {
                $carteirasIds = $context['filters']['carteirasIds'];
            }
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias . '.carteira IN (:ids)')
                ->setParameter('ids', $carteirasIds);
        }
    }


}
