<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class VendaItemEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaItemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return VendaItem::class;
    }
}