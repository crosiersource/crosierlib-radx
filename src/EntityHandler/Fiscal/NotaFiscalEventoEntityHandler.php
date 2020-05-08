<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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