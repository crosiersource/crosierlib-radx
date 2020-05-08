<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

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
