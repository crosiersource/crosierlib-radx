<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\MovimentacaoItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoItemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return MovimentacaoItem::class;
    }

}