<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao;

/**
 * Class OperadoraCartaoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class OperadoraCartaoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return OperadoraCartao::class;
    }

}