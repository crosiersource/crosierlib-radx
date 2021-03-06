<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompraItem;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

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
