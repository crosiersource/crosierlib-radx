<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class DeptoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Depto::class;
    }

    public function beforeSave(/** @var Depto $depto */ $depto)
    {
        if (!$depto->UUID) {
            $depto->UUID = StringUtils::guidv4();
        }
    }

}