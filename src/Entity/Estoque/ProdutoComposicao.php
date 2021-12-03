<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"produtoComposicao","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"produtoComposicao"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/produtoComposicao/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/produtoComposicao/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/produtoComposicao/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/produtoComposicao", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/produtoComposicao", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoComposicaoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoComposicaoRepository")
 * @ORM\Table(name="est_produto_composicao")
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoComposicao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto", inversedBy="composicoes")
     * @ORM\JoinColumn(name="produto_pai_id", nullable=false)
     * @Groups("produtoComposicao")
     *
     * @var null|Produto
     */
    public ?Produto $produtoPai = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto")
     * @ORM\JoinColumn(name="produto_filho_id", nullable=false)
     *
     * @Groups("produtoComposicao")
     *
     * @var null|Produto
     */
    public ?Produto $produtoFilho = null;

    /**
     *
     * @ORM\Column(name="ordem", type="integer", nullable=true)
     * @Groups("produtoComposicao")
     * @var null|integer
     */
    public ?int $ordem = null;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", nullable=false)
     * @Groups("produtoComposicao")
     *
     * @var null|float
     */
    public ?float $qtde = null;

    /**
     *
     * @ORM\Column(name="preco_composicao", type="decimal", nullable=false)
     * @Groups("produtoComposicao")
     * @var null|string
     */
    public ?string $precoComposicao = null;

    

    /**
     * @return float|null
     */
    public function getTotalAtual(): ?float
    {
        return bcmul($this->produtoFilho->jsonData['qtde_estoque_total'] ?? 0.0, $this->produtoFilho->jsonData['preco_tabela'], 2);
    }

    /**
     * @return float|null
     */
    public function getTotalComposicao(): ?float
    {
        return bcmul($this->qtde ?? 0.0, $this->precoComposicao ?? 0.0, 2);
    }


}