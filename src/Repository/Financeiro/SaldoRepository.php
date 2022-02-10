<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;

/**
 * @author Carlos Eduardo Pauluk
 */
class SaldoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Saldo::class;
    }

}
