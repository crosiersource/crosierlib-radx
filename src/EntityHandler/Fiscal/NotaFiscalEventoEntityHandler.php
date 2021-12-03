<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento;

/**
 * Class NotaFiscalEventoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalEventoEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return NotaFiscalEvento::class;
    }
}