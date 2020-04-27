<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class BandeiraCartaoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class BandeiraCartaoEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return BandeiraCartao::class;
    }
}