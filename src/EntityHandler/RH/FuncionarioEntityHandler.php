<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\RH;

use CrosierSource\CrosierLibRadxBundle\Entity\RH\Funcionario;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * EntityHandler para a entidade Funcionario.
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class FuncionarioEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Funcionario::class;
    }
}