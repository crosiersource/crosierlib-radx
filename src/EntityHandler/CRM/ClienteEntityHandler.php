<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;

/**
 * @author Carlos Eduardo Pauluk
 */
class ClienteEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return Cliente::class;
    }

    public function beforeSave(/** @var Cliente $cliente */ $cliente)
    {
        // CPF/CNPJ que comecem com 'G' são considerados "gerados", apenas para marcar posição.
        $cliente->documento = preg_replace("/[^G^0-9]/", "", strtoupper($cliente->documento));
        if (strlen($cliente->documento) === 14) {
            $cliente->tipoPessoa = 'PJ';
            $cliente->jsonData['tipo_pessoa'] = 'PJ';
        } else {
            $cliente->tipoPessoa = 'PF';
            $cliente->jsonData['tipo_pessoa'] = 'PF';
        }
    }


}