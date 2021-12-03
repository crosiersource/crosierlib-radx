<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"depreciacaoPreco","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"depreciacaoPreco"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/depreciacaoPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/depreciacaoPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/depreciacaoPreco/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/depreciacaoPreco", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/depreciacaoPreco", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(OrderFilter::class, properties={"id", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\DepreciacaoPrecoEntityHandler")
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\DepreciacaoPrecoRepository")
 * @ORM\Table(name="est_depreciacao_preco")
 *
 * @author Carlos Eduardo Pauluk
 */
class DepreciacaoPreco implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="porcentagem", type="decimal", nullable=false)
     * @Groups("depreciacaoPreco")
     */
    private ?float $porcentagem;

    /**
     * @ORM\Column(name="prazo_fim", type="integer", nullable=false)
     * @Groups("depreciacaoPreco")
     */
    private ?int $prazoFim;

    /**
     * @ORM\Column(name="prazo_ini", type="integer", nullable=false)
     * @Groups("depreciacaoPreco")
     */
    private ?int $prazoIni;

}