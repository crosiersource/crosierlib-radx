<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;

/**
 * Class CentroCustoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CentroCustoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return CentroCusto::class;
    }

}