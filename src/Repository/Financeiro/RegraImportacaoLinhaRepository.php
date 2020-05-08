<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegraImportacaoLinha;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository para a entidade RegraImportacaoLinha.
 *
 * @author Carlos Eduardo Pauluk
 */
class RegraImportacaoLinhaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return RegraImportacaoLinha::class;
    }

    public function handleFrombyFilters(QueryBuilder $qb)
    {
        return $qb->from($this->getEntityClass(), 'e')
            ->leftJoin(Carteira::class, 'carteira', 'WITH', 'e.carteira = carteira')
            ->leftJoin(Carteira::class, 'carteiraDestino', 'WITH', 'e.carteiraDestino = carteiraDestino')
            ->leftJoin(Modo::class, 'modo', 'WITH', 'e.modo = modo')
            ->leftJoin(CentroCusto::class, 'centroCusto', 'WITH', 'e.centroCusto = centroCusto')
            ->leftJoin(Categoria::class, 'categoria', 'WITH', 'e.categoria = categoria');
    }

    public function findAllBy(Carteira $carteira)
    {
        $ql = 'SELECT r FROM CrosierSource\CrosierLibRadxBundle\\Entity\\Financeiro\\RegraImportacaoLinha r WHERE '
            . 'r.carteira IS NULL OR '
            . 'r.carteiraDestino IS NULL OR '
            . 'r.carteiraDestino = :carteira OR '
            . 'r.carteira = :carteira';

        $qry = $this->getEntityManager()->createQuery($ql);
        $qry->setParameter('carteira', $carteira);
        return $qry->getResult();
    }
}
                        