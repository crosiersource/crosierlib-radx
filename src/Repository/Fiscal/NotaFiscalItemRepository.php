<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;

/**
 * Repository para a entidade NotaFiscalItem.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalItemRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NotaFiscalItem::class;
    }


}
