<?php


namespace CrosierSource\CrosierLibRadxBundle\ApiPlatform\Financeiro;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegistroConferencia;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegraImportacaoLinha;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

/**
 * @author Carlos Eduardo Pauluk
 */
class EstabelecimentoRestrainsExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    private Security $security;


    public function __construct(Security $security)
    {
        $this->security = $security;
    }


    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }


    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }


    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass)
    {
        // As entidades abaixo são sempre filtradas de acordo com o Estabelecimento do usuário logado,
        $arr = [
            Cadeia::class,
            CaixaOperacao::class,
            Carteira::class,
            CentroCusto::class,
            Fatura::class,
            Grupo::class,
            GrupoItem::class,
            Movimentacao::class,
            RegistroConferencia::class,
            RegraImportacaoLinha::class,
            Saldo::class,
        ];

        if (!in_array($resourceClass, $arr, true)) {
            return;
        }

//        // ROLE_ADMIN tem acesso a tudo.
//        if ($this->security->isGranted('ROLE_ADMIN')) {
//            return;
//        }

        $estabelecimentoId = $this->security->getUser()->getEstabelecimentoId();

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere($rootAlias . '.estabelecimentoId  = :estabelecimentoId')
            ->setParameter('estabelecimentoId', $estabelecimentoId);

    }


}