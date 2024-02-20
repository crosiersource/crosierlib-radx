<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\Cte;

/**
 * @author Carlos Eduardo Pauluk
 */
class CteEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Cte::class;
    }

    /**
     * @param Cte $cte
     */
    public function beforeSave($cte)
    {
        if (!$cte->uuid) {
            $cte->uuid = StringUtils::guidv4();
        }
    }


}
