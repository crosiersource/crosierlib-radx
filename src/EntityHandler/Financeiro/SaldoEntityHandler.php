<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;

/**
 * @author Carlos Eduardo Pauluk
 */
class SaldoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Saldo::class;
    }

}