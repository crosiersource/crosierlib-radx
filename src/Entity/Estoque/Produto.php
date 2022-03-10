<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\ApiPlatform\Filter\LikeFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\TrackedEntity;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use CrosierSource\CrosierLibBaseBundle\ApiPlatform\Filter\JsonFilter;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"produto","fornecedor","produtoPreco","produtoSaldo","listaPreco","unidade","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"produto"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ESTOQUE') or is_granted('ROLE_VENDAS')"},
 *          "put"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ESTOQUE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/produto", "security"="is_granted('ROLE_ESTOQUE') or is_granted('ROLE_VENDAS')"},
 *          "post"={"path"="/est/produto", "security"="is_granted('ROLE_ESTOQUE')"}
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
 *     "id": "exact",
 *     "status": "exact",
 *     "codigo": "exact",
 *     "depto": "exact",
 *     "grupo": "exact",
 *     "subgrupo": "exact",
 *     "nome": "partial"
 * })
 * 
 * @ApiFilter(LikeFilter::class, properties={"nome"})
 * 
 * 
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 * 
 * @ApiFilter(JsonFilter::class, properties={
 *     "jsonData.referencias_extras"={ "type": "string", "strategy": "partial" }
 * })
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository")
 * @ORM\Table(name="est_produto")
 * @TrackedEntity()
 *
 * @author Carlos Eduardo Pauluk
 */
class Produto implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("produto")
     * @var string|null
     */
    public ?string $UUID = null;
    

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto")
     * @ORM\JoinColumn(name="depto_id", nullable=false)
     * @Groups("produto")
     * @MaxDepth(1)
     * @var $depto null|Depto
     */
    public ?Depto $depto = null;
    

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo")
     * @ORM\JoinColumn(name="grupo_id", nullable=false)
     * @Groups("produto")
     * @MaxDepth(1)
     * @var $grupo null|Grupo
     */
    public ?Grupo $grupo = null;
    

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo")
     * @ORM\JoinColumn(name="subgrupo_id", nullable=false)
     * @Groups("produto")
     * @MaxDepth(1)
     * @var $subgrupo null|Subgrupo
     */
    public ?Subgrupo $subgrupo = null;
    

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor")
     * @ORM\JoinColumn(name="fornecedor_id", nullable=false)
     * @Groups("produto")
     * @var $fornecedor null|Fornecedor
     */
    public ?Fornecedor $fornecedor = null;
    

    /**
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $codigo = null;


    /**
     * @ORM\Column(name="referencia", type="string", nullable=true)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $referencia = null;


    /**
     * @ORM\Column(name="ean", type="string", nullable=true)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $ean = null;
    

    /**
     * @ORM\Column(name="nome", type="string", nullable=false)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $nome = null;


    /**
     * @ORM\Column(name="marca", type="string", nullable=true)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $marca = null;
    

    /**
     * ATIVO,INATIVO
     * @ORM\Column(name="status", type="string", nullable=true)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $status = null;
    

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_padrao_id", nullable=false)
     * @Groups("produto")
     * @var null|Unidade
     */
    public ?Unidade $unidadePadrao = null;
    

    /**
     * @ORM\Column(name="qtde_total", type="decimal", nullable=false, precision=15, scale=3)
     * @var null|float
     */
    public ?float $qtdeTotal = null;
    

    /**
     * @ORM\Column(name="qtde_minima", type="decimal", nullable=true, precision=15, scale=3)
     * @var null|float
     */
    public ?float $qtdeMinima = null;
    

    /**
     * S,N
     * @ORM\Column(name="composicao", type="string", nullable=true)
     * @Groups("produto")
     * @var null|string
     */
    public ?string $composicao = 'N';

    
    /**
     * Informa se o produto está em e-commerce.
     * 
     * @ORM\Column(name="ecommerce", type="boolean", nullable=true)
     * @Groups("produto")
     */
    public ?bool $ecommerce = false;

    /**
     * Marca a última data de integração ao e-commerce.
     *
     * @ORM\Column(name="dt_ult_integracao_ecommerce", type="datetime", nullable=true)
     * @Groups("produto")
     */
    public ?DateTime $dtUltIntegracaoEcommerce = null;
    

    /**
     * @ORM\OneToMany(targetEntity="ProdutoImagem", mappedBy="produto", cascade={"all"}, orphanRemoval=true)
     * @var ProdutoImagem[]|ArrayCollection|null
     * @ORM\OrderBy({"ordem" = "ASC"})
     */
    public $imagens;
    

    /**
     * @ORM\OneToMany(targetEntity="ProdutoComposicao", mappedBy="produtoPai", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ProdutoComposicao[]|ArrayCollection|null
     * @ORM\OrderBy({"ordem" = "ASC"})
     */
    public $composicoes;
    

    /**
     * @ORM\OneToMany(targetEntity="ProdutoPreco", mappedBy="produto", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"atual" = "DESC"})
     * @var ProdutoPreco[]|ArrayCollection|null
     * @Groups("produto")
     */
    public $precos;
    

    /**
     * @var array
     */
    private array $precosPorLista = [];
    

    /**
     * @ORM\OneToMany(targetEntity="ProdutoSaldo", mappedBy="produto", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ProdutoSaldo[]|ArrayCollection|null
     * @Groups("produto")
     */
    public $saldos;
    

    /**
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("produto")
     */
    public ?array $jsonData = null;


    public function __construct()
    {
        $this->imagens = new ArrayCollection();
        $this->composicoes = new ArrayCollection();
        $this->precos = new ArrayCollection();
        $this->saldos = new ArrayCollection();
    }


    /**
     * @return ProdutoImagem[]|ArrayCollection|null
     */
    public function getImagens()
    {
        return $this->imagens;
    }

    /**
     * @param ProdutoImagem[]|ArrayCollection|null $imagens
     * @return Produto
     */
    public function setImagens($imagens): Produto
    {
        $this->imagens = $imagens;
        return $this;
    }


    /**
     * @return ProdutoComposicao[]|ArrayCollection|null
     */
    public function getComposicoes()
    {
        return $this->composicoes;
    }

    /**
     * @param ProdutoComposicao[]|ArrayCollection|null $composicoes
     * @return Produto
     */
    public function setComposicoes($composicoes): Produto
    {
        $this->composicoes = $composicoes;
        return $this;
    }

    /**
     * @return array
     */
    public function getPrecosPorLista(): array
    {
        if (!$this->precosPorLista) {
            $precosPorLista = [];
            foreach ($this->precos as $preco) {
                $precosPorLista[strtoupper($preco->lista->descricao)] = $preco->precoPrazo;
            }
            $precosPorLista['VAREJO'] = $precosPorLista['VAREJO'] ?? 0.0;
            $precosPorLista['ATACADO'] = $precosPorLista['ATACADO'] ?? 0.0;
            $this->precosPorLista = $precosPorLista;
        }
        return $this->precosPorLista;
    }

    /**
     * @SerializedName("descricaoMontada")
     * @Groups("produto")
     * @return string
     */
    public function getDescricaoMontada(): string {
        return $this->codigo . ' - ' . $this->nome . ' (' . $this->unidadePadrao->label . ')';
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produto")
     * @SerializedName("qtdeTotal")
     * @return float
     */
    public function getQtdeTotalFormatted(): float
    {
        return (float)$this->qtdeTotal;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produto")
     * @SerializedName("qtdeTotal")
     * @param float $qtdeTotal
     */
    public function setQtdeTotalFormatted(float $qtdeTotal)
    {
        $this->qtdeTotal = $qtdeTotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produto")
     * @SerializedName("qtdeMinima")
     * @return float
     */
    public function getQtdeMinimaFormatted(): float
    {
        return (float)$this->qtdeMinima;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("produto")
     * @SerializedName("qtdeMinima")
     * @param float $qtdeMinima
     */
    public function setQtdeMinimaFormatted(float $qtdeMinima)
    {
        $this->qtdeMinima = $qtdeMinima;
    }


}
