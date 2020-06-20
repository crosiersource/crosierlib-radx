<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\RomaneioItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class RomaneioItemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return RomaneioItem::class;
    }

    public function beforeSave($item)
    {
        /** @var RomaneioItem $item */
        if (!$item->ordem) {
            $ultimaOrdem = 0;
            foreach ($item->romaneio->itens as $item) {
                if ($item->ordem > $ultimaOrdem) {
                    $ultimaOrdem = $item->ordem;
                }
            }
            $item->ordem = ($ultimaOrdem + 1);
        }
    }
}