<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalVenda;

/**
 * Repository para a entidade NotaFiscalVenda.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalVendaRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NotaFiscalVenda::class;
    }

}
