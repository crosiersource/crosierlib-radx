<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade para manter registros de conferências mensais.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/registroConferencia", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/registroConferencia", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @ApiFilter(SearchFilter::class,
 *     properties={
 *     "id": "exact",
 *     "descricao": "partial"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtVencto", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\RegistroConferenciaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\RegistroConferenciaRepository")
 * @ORM\Table(name="fin_reg_conf")
 *
 * @author Carlos Eduardo Pauluk
 */
class RegistroConferencia implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     * @ORM\Column(name="dt_registro", type="datetime")
     * @Groups("entity")
     */
    public ?\Datetime $dtRegistro = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("entity")
     */
    public ?Carteira $carteira = null;

    /**
     * @ORM\Column(name="valor", type="decimal")
     * @Groups("entity")
     */
    public ?float $valor = null;

    /**
     * @ORM\Column(name="obs", type="string")
     * @Groups("entity")
     */
    public ?string $obs = null;


}
