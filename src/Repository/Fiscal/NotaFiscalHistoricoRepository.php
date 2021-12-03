<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;

/**
 * Repository para a entidade NotaFiscalHistorico.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalHistoricoRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NotaFiscal::class;
    }
}
