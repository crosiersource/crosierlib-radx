<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"listaPreco","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"listaPreco"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/listaPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/listaPreco/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/listaPreco/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/listaPreco", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/listaPreco", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"descricao": "partial", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ListaPrecoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ListaPrecoRepository")
 * @ORM\Table(name="est_lista_preco")
 *
 * @author Carlos Eduardo Pauluk
 */
class ListaPreco implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false)
     * @Groups("listaPreco")
     *
     * @var string|null
     */
    public ?string $descricao;

    /**
     *
     * @ORM\Column(name="dt_vigencia_ini", type="datetime", nullable=false)
     * @Groups("listaPreco")
     *
     * @var \DateTime|null
     */
    public ?\DateTime $dtVigenciaIni;

    /**
     *
     * @ORM\Column(name="dt_vigencia_fim", type="datetime", nullable=true)
     * @Groups("listaPreco")
     *
     * @var \DateTime|null
     */
    public ?\DateTime $dtVigenciaFim;


}
