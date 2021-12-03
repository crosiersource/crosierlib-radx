<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompraItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class PedidoCompraItemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return PedidoCompraItem::class;
    }

    public function beforeSave($item)
    {
        /** @var PedidoCompraItem $item */
        if (!$item->ordem) {
            $ultimaOrdem = 0;
            foreach ($item->pedidoCompra->itens as $item) {
                if ($item->ordem > $ultimaOrdem) {
                    $ultimaOrdem = $item->ordem;
                }
            }
            $item->ordem = ($ultimaOrdem + 1);
        }
    }
}