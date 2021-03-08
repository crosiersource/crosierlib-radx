<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/produtoPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/produtoPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/produtoPreco/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/produtoPreco", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/produtoPreco", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoPrecoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoPrecoRepository")
 * @ORM\Table(name="est_produto_preco")
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoPreco implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco")
     * @ORM\JoinColumn(name="lista_id", nullable=false)
     * @Groups("entity")
     * @var null|ListaPreco
     */
    public ?ListaPreco $lista = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto", inversedBy="precos")
     * @ORM\JoinColumn(name="produto_id", nullable=false)
     *
     * @var null|Produto
     */
    public ?Produto $produto = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_id", nullable=false)
     * @Groups("entity")
     *
     * @var null|Unidade
     */
    public ?Unidade $unidade = null;

    /**
     *
     * @ORM\Column(name="coeficiente", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $coeficiente = null;

    /**
     *
     * @ORM\Column(name="custo_operacional", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $custoOperacional = null;

    /**
     *
     * @ORM\Column(name="dt_custo", type="date", nullable=false)
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtCusto = null;

    /**
     *
     * @ORM\Column(name="dt_preco_venda", type="date", nullable=false)
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtPrecoVenda = null;

    /**
     *
     * @ORM\Column(name="margem", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $margem = null;

    /**
     *
     * @ORM\Column(name="prazo", type="integer", nullable=false)
     * @Groups("entity")
     *
     * @var null|integer
     */
    public ?int $prazo = null;

    /**
     *
     * @ORM\Column(name="preco_custo", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoCusto = null;

    /**
     *
     * @ORM\Column(name="preco_prazo", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoPrazo = null;

    /**
     *
     * @ORM\Column(name="preco_promo", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoPromo = null;

    /**
     *
     * @ORM\Column(name="preco_vista", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoVista = null;

    /**
     *
     * @ORM\Column(name="custo_financeiro", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $custoFinanceiro = null;

    /**
     *
     * @ORM\Column(name="atual", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $atual = false;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}