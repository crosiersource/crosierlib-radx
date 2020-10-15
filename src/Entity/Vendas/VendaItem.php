<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaItemRepository")
 * @ORM\Table(name="ven_venda_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaItem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda", inversedBy="itens")
     * @ORM\JoinColumn(name="venda_id", nullable=false)     *
     *
     * @var null|Venda
     */
    public ?Venda $venda = null;

    /**
     *
     * @ORM\Column(name="ordem", type="integer")
     * @Groups("entity")
     *
     * @var null|integer
     */
    public ?int $ordem = null;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", nullable=false)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $qtde = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_id", nullable=false)
     *
     * @var null|Unidade
     */
    public ?Unidade $unidade = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto")
     * @ORM\JoinColumn(name="produto_id")
     * @Groups("entity")
     *
     * @var null|Produto
     */
    public ?Produto $produto = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="preco_venda", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoVenda = null;

    /**
     *
     * @ORM\Column(name="subtotal", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $subtotal = null;

    /**
     *
     * @ORM\Column(name="desconto", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $desconto = null;

    /**
     * @ORM\Column(name="total", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $total = null;

    /**
     *
     * @ORM\Column(name="devolucao", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $devolucao = false;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


    public function getDescricaoMontadaResumida(int $tam = 36) {
        $desc = '[';
        if (strlen($this->produto->codigo) > 6) {
            $desc .= substr($this->produto->codigo, -6);
        } else {
            $desc .= str_pad($this->produto->codigo, 6, '0', STR_PAD_LEFT);
        }
        $desc .= '] ';
        if (strlen($this->produto->nome) > $tam) {
            $desc .= substr($this->produto->nome, 0, $tam - 10) . '..' . substr($this->produto->nome, -10);
        } else {
            $desc .= $this->produto->nome;
        }

        return mb_strtoupper($desc);

    }


}