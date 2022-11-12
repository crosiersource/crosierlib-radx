<?php


namespace CrosierSource\CrosierLibRadxBundle\ApiPlatform\Financeiro;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\TotalDoctor\Associado;
use App\Entity\TotalDoctor\UsuarioClientePj;
use App\Repository\TotalDoctor\UsuarioClientePjRepository;
use CrosierSource\CrosierLibBaseBundle\Entity\Security\User;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

/**
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoRestrainsExtension implements ContextAwareQueryCollectionExtensionInterface
{
    
    public function applyToCollection(QueryBuilder $queryBuilder, 
                                      QueryNameGeneratorInterface $queryNameGenerator, 
                                      string $resourceClass, 
                                      string $operationName = null, 
                                      array $context = [])
    {
        if ($resourceClass === Movimentacao::class && $context['filters']['carteirasIds'] ?? false) {
            $carteirasIds = explode(',', $context['filters']['carteirasIds']);
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias . '.carteira IN (:ids)')
                ->setParameter('ids', $carteirasIds);
        }
    }


}
