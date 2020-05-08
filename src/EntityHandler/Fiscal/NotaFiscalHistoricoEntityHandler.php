<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalHistorico;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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