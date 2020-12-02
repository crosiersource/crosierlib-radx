<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao;

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