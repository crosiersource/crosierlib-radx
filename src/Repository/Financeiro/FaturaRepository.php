<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;

/**
 * Repository para a entidade Movimentacao.
 *
 * @author Carlos Eduardo Pauluk
 */
class FaturaRepository extends FilterRepository
{


    public function getEntityClass(): string
    {
        return Fatura::class;
    }

}
