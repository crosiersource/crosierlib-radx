<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento;

/**
 * Repository para a entidade NotaFiscalEvento.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalEventoRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NotaFiscalEvento::class;
    }


}
