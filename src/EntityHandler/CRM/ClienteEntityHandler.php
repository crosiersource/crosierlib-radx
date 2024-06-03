<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
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
    
    public function getProxCodigo(): int {        
        $sql = 'SELECT MAX(CAST(codigo AS UNSIGNED)) + 1 as prox FROM crm_cliente WHERE codigo < 2147483647';
        $rsProxCodigo = $this->getDoctrine()->getConnection()->fetchAssociative($sql);
        return $rsProxCodigo['prox'] ?? 1;
    }

    public function beforeSave(/** @var Cliente $cliente */ $cliente)
    {
        if (!$cliente->codigo) {
            $cliente->codigo = $this->getProxCodigo();
        }
        $cliente->ativo = $cliente->ativo === NULL ? true : $cliente->ativo;
        // CPF/CNPJ que comecem com 'G' são considerados "gerados", apenas para marcar posição.
        $cliente->documento = preg_replace("/[^G^0-9]/", "", strtoupper($cliente->documento));
        $cliente->cep = preg_replace("/[^0-9]/", "", $cliente->cep);
        if (strlen($cliente->documento) === 14) {
            $cliente->tipoPessoa = 'PJ';
            $cliente->jsonData['tipo_pessoa'] = 'PJ';
        } else {
            $cliente->tipoPessoa = 'PF';
            $cliente->jsonData['tipo_pessoa'] = 'PF';
        }
    }


}
