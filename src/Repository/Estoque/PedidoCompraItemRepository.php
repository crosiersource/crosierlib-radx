<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompraItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class PedidoCompraItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return PedidoCompraItem::class;
    }
}
