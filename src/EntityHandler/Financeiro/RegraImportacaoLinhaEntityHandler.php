<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegraImportacaoLinha;

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