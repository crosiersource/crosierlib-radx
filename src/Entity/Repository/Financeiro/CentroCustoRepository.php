<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade CentroCusto.
 *
 * @author Carlos Eduardo Pauluk
 */
class CentroCustoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return CentroCusto::class;
    }
}
