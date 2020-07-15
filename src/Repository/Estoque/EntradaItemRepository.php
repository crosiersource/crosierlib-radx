<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\EntradaItem;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class EntradaItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return EntradaItem::class;
    }
}
