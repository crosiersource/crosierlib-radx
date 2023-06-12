<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;

/**
 * Class CategoriaEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CategoriaEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Categoria::class;
    }

    /**
     * @param Categoria $categoria
     * @return mixed|void
     */
    public function beforeSave($categoria)
    {
        $categoria->codigoSuper = substr($categoria->codigo, 0, 1);
        $categoria->codigoOrd = str_pad($categoria->codigo, 18, 0);
        if (!$categoria->getId() && $categoria->pai && $categoria->pai->codigo && strlen((string)$categoria->pai->codigo) > 9) {
            throw new ViewException('Não é possível criar categoria abaixo do quinto nível');
        }
        $categoria->setId($categoria->codigo);
    }


}