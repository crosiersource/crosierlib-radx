<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"produtoPreco","listaPreco","unidade","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"produtoPreco"},"enable_max_depth"=true},
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
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "nome": "partial",
 *     "produto": "exact",
 *     "documento": "exact",
 *     "id": "exact"
 * })
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
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco")
     * @ORM\JoinColumn(name="lista_id", nullable=false)
     * @Groups("produtoPreco")
     * @var null|ListaPreco
     */
    public ?ListaPreco $lista = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto", inversedBy="precos")
     * @ORM\JoinColumn(name="produto_id", nullable=false)
     * @Groups("produtoPreco")
     * @var null|Produto
     */
    public ?Produto $produto = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_id", nullable=false)
     * @Groups("produtoPreco")
     * @var null|Unidade
     */
    public ?Unidade $unidade = null;

    /**
     * @ORM\Column(name="coeficiente", type="decimal", nullable=true, precision=15, scale=2)
     * @var null|float
     */
    public ?float $coeficiente = null;

    /**
     * @ORM\Column(name="custo_operacional", type="decimal", nullable=false, precision=15, scale=2)
     * @var null|float
     */
    public ?float $custoOperacional = 35.0;

    /**
     * @ORM\Column(name="dt_custo", type="date", nullable=false)
     * @Groups("produtoPreco")
     * @var null|DateTime
     */
    public ?DateTime $dtCusto = null;

    /**
     * @ORM\Column(name="dt_preco_venda", type="date", nullable=false)
     * @Groups("produtoPreco")
     * @var null|DateTime
     */
    public ?DateTime $dtPrecoVenda = null;

    /**
     * @ORM\Column(name="margem", type="decimal", nullable=false, precision=15, scale=2)
     * @var null|float
     */
    public ?float $margem = null;

    /**
     * @ORM\Column(name="prazo", type="integer", nullable=false)
     * @Groups("produtoPreco")
     * @var null|integer
     */
    public ?int $prazo = null;

    /**
     * @ORM\Column(name="preco_custo", type="decimal", nullable=false, precision=15, scale=2)
     * @var null|float
     */
    public ?float $precoCusto = null;

    /**
     * @ORM\Column(name="preco_prazo", type="decimal", nullable=false, precision=15, scale=2)
     * @var null|float
     */
    public ?float $precoPrazo = null;

    /**
     * @ORM\Column(name="preco_promo", type="decimal", nullable=true, precision=15, scale=2)
     * @var null|float
     */
    public ?float $precoPromo = null;

    /**
     * @ORM\Column(name="preco_vista", type="decimal", nullable=false, precision=15, scale=2)
     * @var null|float
     */
    public ?float $precoVista = null;

    /**
     * @ORM\Column(name="custo_financeiro", type="decimal", nullable=true, precision=15, scale=2)
     * @var null|float
     */
    public ?float $custoFinanceiro = null;

    /**
     * @ORM\Column(name="atual", type="boolean")
     * @Groups("produtoPreco")
     * @var bool|null
     */
    public ?bool $atual = false;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("produtoPreco")
     */
    public ?array $jsonData = null;


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoCusto")
     * @return float
     */
    public function getPrecoCustoFormatted(): float
    {
        return (float)$this->precoCusto;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoCusto")
     * @param float $precoCusto
     */
    public function setPrecoCustoFormatted(float $precoCusto)
    {
        $this->precoCusto = $precoCusto;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoPrazo")
     * @return float
     */
    public function getPrecoPrazoFormatted(): float
    {
        return (float)$this->precoPrazo;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoPrazo")
     * @param float $precoPrazo
     */
    public function setPrecoPrazoFormatted(float $precoPrazo)
    {
        $this->precoPrazo = $precoPrazo;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoVista")
     * @return float
     */
    public function getPrecoVistaFormatted(): float
    {
        return (float)$this->precoVista;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoVista")
     * @param float $precoVista
     */
    public function setPrecoVistaFormatted(float $precoVista)
    {
        $this->precoVista = $precoVista;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoPromo")
     * @return float
     */
    public function getPrecoPromoFormatted(): float
    {
        return (float)$this->precoPromo;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("precoPromo")
     * @param float $precoPromo
     */
    public function setPrecoPromoFormatted(float $precoPromo)
    {
        $this->precoPromo = $precoPromo;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("margem")
     * @return float
     */
    public function getMargemFormatted(): float
    {
        return (float)$this->margem;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("margem")
     * @param float $margem
     */
    public function setMargemFormatted(float $margem)
    {
        $this->margem = $margem;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("custoFinanceiro")
     * @return float
     */
    public function getCustoFinanceiroFormatted(): float
    {
        return (float)$this->custoFinanceiro;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("custoFinanceiro")
     * @param float $custoFinanceiro
     */
    public function setCustoFinanceiroFormatted(float $custoFinanceiro)
    {
        $this->custoFinanceiro = $custoFinanceiro;
    }



    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("coeficiente")
     * @return float
     */
    public function getCoeficienteFormatted(): float
    {
        return (float)$this->coeficiente;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("coeficiente")
     * @param float $coeficiente
     */
    public function setCoeficienteFormatted(float $coeficiente)
    {
        $this->coeficiente = $coeficiente;
    }



    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("custoOperacional")
     * @return float
     */
    public function getCustoOperacionalFormatted(): float
    {
        return (float)$this->custoOperacional;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produtoPreco")
     * @SerializedName("custoOperacional")
     * @param float $custoOperacional
     */
    public function setCustoOperacionalFormatted(float $custoOperacional)
    {
        $this->custoOperacional = $custoOperacional;
    }


}