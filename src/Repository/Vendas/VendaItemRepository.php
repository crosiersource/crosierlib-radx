<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade VendaItem.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class VendaItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return VendaItem::class;
    }
}
