<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\EntradaItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class EntradaItemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return EntradaItem::class;
    }

}