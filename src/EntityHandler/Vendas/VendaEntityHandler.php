<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class VendaEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Venda::class;
    }
}