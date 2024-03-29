<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\RH;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;

/**
 * EntityHandler para a entidade Colaborador.
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\RH
 * @author Carlos Eduardo Pauluk
 */
class ColaboradorEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Colaborador::class;
    }

    public function beforeSave(/** @var Colaborador $colaborador */ $colaborador)
    {
        $colaborador->cpf = preg_replace("/[^0-9]/", "", $colaborador->cpf);
    }


}