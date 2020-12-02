<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\ImportExtratoCabec;

/**
 * Class ImportExtratoCabec
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class ImportExtratoCabecEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return ImportExtratoCabec::class;
    }

}