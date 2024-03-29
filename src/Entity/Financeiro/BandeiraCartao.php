<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

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
 * Entidade Bandeira de Cartão.
 * Ex.: MASTER MAESTRO, MASTER, VISA ELECTRON, VISA, etc.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"bandeiraCartao","modo","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"bandeiraCartao"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/bandeiraCartao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/bandeiraCartao/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"},
 *          "delete"={"path"="/fin/bandeiraCartao/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/bandeiraCartao", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/bandeiraCartao", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"descricao": "partial", "id": "exact", "modo": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\BandeiraCartaoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\BandeiraCartaoRepository")
 * @ORM\Table(name="fin_bandeira_cartao")
 *
 * @author Carlos Eduardo Pauluk
 */
class BandeiraCartao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("bandeiraCartao")
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("bandeiraCartao")
     */
    public ?Modo $modo = null;

    /**
     * Para marcar diferentes nomes que podem ser utilizados para definir uma bandeira (ex.: MAESTRO ou MASTER MAESTRO ou M MAESTRO).
     *
     * @ORM\Column(name="labels", type="string")
     * @Groups("bandeiraCartao")
     */
    public ?string $labels = null;


}
