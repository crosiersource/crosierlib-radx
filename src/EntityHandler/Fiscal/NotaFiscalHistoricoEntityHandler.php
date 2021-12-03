<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalHistorico;

/**
 * Class NotaFiscalHistoricoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalHistoricoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return NotaFiscalHistorico::class;
    }
}