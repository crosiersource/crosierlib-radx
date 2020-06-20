<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;

/**
 * @author Carlos Eduardo Pauluk
 */
class FornecedorEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Fornecedor::class;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return Fornecedor
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function fornecedorFromNotaFiscal(NotaFiscal $notaFiscal): Fornecedor
    {
        $fornecedor = $this->getDoctrine()->getRepository(Fornecedor::class)->findOneBy(['documento' => $notaFiscal->getDocumentoEmitente()]);
        if (!$fornecedor) {
            $fornecedor = new Fornecedor();

            $fornecedor->nome = $notaFiscal->getXNomeEmitente();
            $fornecedor->nomeFantasia = $notaFiscal->getXNomeEmitente();
            $fornecedor->documento = $notaFiscal->getDocumentoEmitente();
            $fornecedor->inscricaoEstadual = $notaFiscal->getInscricaoEstadualEmitente();
            $fornecedor->jsonData = [
                'enderecos' => [
                    [
                        'logradouro' => $notaFiscal->getLogradouroEmitente(),
                        'numero' => $notaFiscal->getNumeroEmitente(),
                        'cep' => $notaFiscal->getCepEmitente(),
                        'bairro' => $notaFiscal->getBairroEmitente(),
                        'cidade' => $notaFiscal->getCidadeEmitente(),
                        'estado' => $notaFiscal->getEstadoEmitente()
                    ],
                ],
                'fones' => [
                    [
                        'fone' => $notaFiscal->getFoneEmitente(),
                        'tipo' => 'COMERCIAL'
                    ]
                ]
            ];
            /** @var Fornecedor $fornecedor */
            $fornecedor = $this->save($fornecedor);
        }
        return $fornecedor;
    }

}