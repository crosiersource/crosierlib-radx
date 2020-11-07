<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;

/**
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM
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
        $cliente->documento = preg_replace("/[^G^0-9]/", "", strtoupper($cliente->documento));
        if (strlen($cliente->documento) === 14) {
            $cliente->jsonData['tipo_pessoa'] = 'PJ';
        } else {
            $cliente->jsonData['tipo_pessoa'] = 'PF';
        }
        
    }



}