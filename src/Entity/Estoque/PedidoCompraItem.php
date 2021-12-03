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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"pedidoCompraItem","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"pedidoCompraItem"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/pedidoCompraItem/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/pedidoCompraItem/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/pedidoCompraItem/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/pedidoCompraItem", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/pedidoCompraItem", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\PedidoCompraItemEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\PedidoCompraItemRepository")
 * @ORM\Table(name="est_pedidocompra_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class PedidoCompraItem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompra", inversedBy="itens")
     * @ORM\JoinColumn(name="pedidocompra_id")
     *
     * @var null|PedidoCompra
     */
    public ?PedidoCompra $pedidoCompra = null;

    /**
     *
     * @ORM\Column(name="ordem", type="integer")
     * @Groups("pedidoCompraItem")
     *
     * @var null|integer
     */
    public ?int $ordem = null;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", precision=15, scale=2)
     * @Groups("pedidoCompraItem")
     *
     * @var null|float
     */
    public ?float $qtde = null;

    /**
     *
     * @ORM\Column(name="referencia", type="string")
     * @Groups("pedidoCompraItem")
     *
     * @var null|string
     */
    public ?string $referencia = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("pedidoCompraItem")
     *
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="preco_custo", type="decimal", precision=15, scale=2)
     * @Groups("pedidoCompraItem")
     *
     * @var null|float
     */
    public ?float $precoCusto = null;

    /**
     *
     * @ORM\Column(name="desconto", type="decimal", precision=15, scale=2)
     * @Groups("pedidoCompraItem")
     *
     * @var null|float
     */
    public ?float $desconto = null;

    /**
     * @ORM\Column(name="total", type="decimal", precision=19, scale=2)
     * @Groups("pedidoCompraItem")
     *
     * @var null|float
     */
    public ?float $total = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("pedidoCompraItem")
     */
    public ?array $jsonData = null;


}