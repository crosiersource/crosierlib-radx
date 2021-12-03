<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Grupo::class;
    }

    public function beforeSave(/** @var Grupo $grupo */ $grupo)
    {
        if (!$grupo->UUID) {
            $grupo->UUID = StringUtils::guidv4();
        }
        $grupo->jsonData['depto_codigo'] = $grupo->depto->codigo;
        $grupo->jsonData['depto_nome'] = $grupo->depto->nome;
    }


}