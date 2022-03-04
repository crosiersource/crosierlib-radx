<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Business\Estoque\CalculoPreco;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoPrecoEntityHandler extends EntityHandler
{

    private CalculoPreco $calculoPreco;

    /**
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param CalculoPreco $calculoPreco
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                CalculoPreco $calculoPreco)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog);
        $this->calculoPreco = $calculoPreco;
    }


    public function getEntityClass(): string
    {
        return ProdutoPreco::class;
    }

    public function beforeSave(/** @var ProdutoPreco $produtoPreco * */ $produtoPreco)
    {
        $produtoPreco->prazo = $produtoPreco->prazo ?? 0;

        $precoArr = [
            'prazo' => $produtoPreco->prazo,
            'margem' => $produtoPreco->margem,
            'custoOperacional' => bcdiv($produtoPreco->custoOperacional, 100.0, 4),
            'custoFinanceiro' => bcdiv($produtoPreco->custoFinanceiro, 100.0, 4),
            'precoCusto' => $produtoPreco->precoCusto,
            'precoPrazo' => $produtoPreco->precoPrazo
        ];

        $this->calculoPreco->calcularPreco($precoArr);

        if (!$produtoPreco->precoPrazo) {
            $produtoPreco->precoPrazo = $precoArr['precoPrazo'];
        }
        if (!$produtoPreco->margem) {
            $produtoPreco->margem = $precoArr['margem'];
        }
        if (!$produtoPreco->coeficiente) {
            $produtoPreco->coeficiente = $precoArr['coeficiente'];
        }
        if (!$produtoPreco->dtCusto) {
            $produtoPreco->dtCusto = new \DateTime();
        }
        if (!$produtoPreco->dtPrecoVenda) {
            $produtoPreco->dtPrecoVenda = new \DateTime();
        }
        if (!$produtoPreco->lista) {
            $lista1 = $this->getDoctrine()->getRepository(ListaPreco::class)->find(1);// GERAL
            $produtoPreco->lista = $lista1;
        }
        if (!$produtoPreco->unidade) {
            $produtoPreco->unidade = $produtoPreco->produto->unidadePadrao;
        }

        $produtoPreco->precoVista = $precoArr['precoVista'];

    }


    public function afterSave(/** @var ProdutoPreco $produtoPreco * */ $produtoPreco)
    {
        if ($produtoPreco->atual) {
            // Só pode ter 1 preço marcado como 'atual' para o mesmo produto, lista e unidade
            $conn = $this->getDoctrine()->getConnection();
            $conn->executeUpdate('UPDATE est_produto_preco SET atual = 0 WHERE id != :id AND produto_id = :produtoId AND lista_id = :listaId AND unidade_id = :unidadeId',
                [
                    'id' => $produtoPreco->getId(),
                    'produtoId' => $produtoPreco->produto->getId(),
                    'listaId' => $produtoPreco->lista->getId(),
                    'unidadeId' => $produtoPreco->unidade->getId()
                ]);
        }
    }


}
