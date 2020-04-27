<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegraImportacaoLinha;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * Class RegraImportacaoLinhaEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class RegraImportacaoLinhaEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return RegraImportacaoLinha::class;
    }
}