<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalVenda;

/**
 * Class NotaFiscalVendaEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 *
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalVendaEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return NotaFiscalVenda::class;
    }
}