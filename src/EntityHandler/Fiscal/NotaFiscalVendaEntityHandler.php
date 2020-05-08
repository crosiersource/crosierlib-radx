<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalVenda;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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