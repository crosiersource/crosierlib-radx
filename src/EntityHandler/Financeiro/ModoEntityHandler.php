<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class ModoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class ModoEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return Modo::class;
    }
}