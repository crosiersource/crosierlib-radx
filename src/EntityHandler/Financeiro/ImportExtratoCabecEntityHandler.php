<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\ImportExtratoCabec;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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