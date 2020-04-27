<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class GrupoItemEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class GrupoItemEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return GrupoItem::class;
    }
}