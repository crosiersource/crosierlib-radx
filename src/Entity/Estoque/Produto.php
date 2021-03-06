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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 *  @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/produto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/produto", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/produto", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository")
 * @ORM\Table(name="est_produto")
 *
 * @author Carlos Eduardo Pauluk
 */
class Produto implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("entity")
     *
     * @var string|null
     */
    public ?string $UUID = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto")
     * @ORM\JoinColumn(name="depto_id", nullable=false)
     * @Groups("entity")
     * @MaxDepth(1)
     * @var $depto null|Depto
     */
    public ?Depto $depto = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo")
     * @ORM\JoinColumn(name="grupo_id", nullable=false)
     * @Groups("entity")
     * @MaxDepth(1)
     * @var $grupo null|Grupo
     */
    public ?Grupo $grupo = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo")
     * @ORM\JoinColumn(name="subgrupo_id", nullable=false)
     * @Groups("entity")
     * @MaxDepth(1)
     * @var $subgrupo null|Subgrupo
     */
    public ?Subgrupo $subgrupo = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor")
     * @ORM\JoinColumn(name="fornecedor_id", nullable=false)
     *
     * @var $fornecedor null|Fornecedor
     */
    public ?Fornecedor $fornecedor = null;

    /**
     *
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="nome", type="string", nullable=false)
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $nome = null;

    /**
     * ATIVO,INATIVO
     *
     * @ORM\Column(name="status", type="string", nullable=true)
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $status = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_padrao_id", nullable=false)
     *
     * @var null|Unidade
     */
    public ?Unidade $unidadePadrao = null;

    /**
     * S,N
     *
     * @ORM\Column(name="composicao", type="string", nullable=true)
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $composicao = 'N';

    /**
     *
     * @ORM\OneToMany(targetEntity="ProdutoImagem", mappedBy="produto", cascade={"all"}, orphanRemoval=true)
     * @var ProdutoImagem[]|ArrayCollection|null
     * @ORM\OrderBy({"ordem" = "ASC"})
     *
     */
    public $imagens;

    /**
     *
     * @ORM\OneToMany(targetEntity="ProdutoComposicao", mappedBy="produtoPai", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ProdutoComposicao[]|ArrayCollection|null
     * @ORM\OrderBy({"ordem" = "ASC"})
     *
     */
    public $composicoes;

    /**
     *
     * @ORM\OneToMany(targetEntity="ProdutoPreco", mappedBy="produto", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"atual" = "DESC"})
     * @var ProdutoPreco[]|ArrayCollection|null
     *
     */
    public $precos;

    /**
     *
     * @var array
     *
     */
    private array $precosPorLista = [];

    /**
     *
     * @ORM\OneToMany(targetEntity="ProdutoSaldo", mappedBy="produto", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ProdutoSaldo[]|ArrayCollection|null
     *
     */
    public $saldos;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
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


}