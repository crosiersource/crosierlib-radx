<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
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

    public function beforeSave(/** @var Fornecedor $fornecedor */ $fornecedor)
    {
        if (!$fornecedor->codigo) {
            $fornecedor->codigo = StringUtils::guidv4();
        }
        
        $fornecedor->documento = preg_replace("/[^0-9]/", "", $fornecedor->documento);
        if (strlen($fornecedor->documento) === 14) {
            $fornecedor->jsonData['tipo_pessoa'] = 'PJ';
        } else {
            $fornecedor->jsonData['tipo_pessoa'] = 'PF';
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return Fornecedor
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function fornecedorFromNotaFiscal(NotaFiscal $notaFiscal): Fornecedor
    {
        $fornecedor = $this->getDoctrine()->getRepository(Fornecedor::class)->findOneBy(['documento' => $notaFiscal->documentoEmitente]);
        if (!$fornecedor) {
            $fornecedor = new Fornecedor();

            $fornecedor->nome = $notaFiscal->xNomeEmitente;
            $fornecedor->nomeFantasia = $notaFiscal->xNomeEmitente;
            $fornecedor->documento = $notaFiscal->documentoEmitente;
            $fornecedor->inscricaoEstadual = $notaFiscal->inscricaoEstadualEmitente;
            $fornecedor->jsonData = [
                'enderecos' => [
                    [
                        'logradouro' => $notaFiscal->logradouroEmitente,
                        'numero' => $notaFiscal->numeroEmitente,
                        'cep' => $notaFiscal->cepEmitente,
                        'bairro' => $notaFiscal->bairroEmitente,
                        'cidade' => $notaFiscal->cidadeEmitente,
                        'estado' => $notaFiscal->estadoEmitente
                    ],
                ],
                'fones' => [
                    [
                        'fone' => $notaFiscal->foneEmitente,
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