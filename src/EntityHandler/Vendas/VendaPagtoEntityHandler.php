<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;

/**
 * @author Carlos Eduardo Pauluk
 */
class VendaPagtoEntityHandler extends EntityHandler
{


    public function getEntityClass(): string
    {
        return VendaPagto::class;
    }


}
