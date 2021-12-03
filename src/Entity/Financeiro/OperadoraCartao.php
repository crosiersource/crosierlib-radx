<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade Operadora de Cartões.
 * Ex.: RDCARD, CIELO, STONE.
 * 
 * @ApiResource(
 *     normalizationContext={"groups"={"operadoraCartao","carteira","user","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"operadoraCartao"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/operadoraCartao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/operadoraCartao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/operadoraCartao/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/operadoraCartao", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/operadoraCartao", "security"="is_granted('ROLE_FINAN')"}
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
 * @ApiFilter(SearchFilter::class,
 *     properties={
 *     "id": "exact",
 *     "descricao": "partial"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "carteira.descricao", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\OperadoraCartaoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\OperadoraCartaoRepository")
 * @ORM\Table(name="fin_operadora_cartao")
 *
 * @author Carlos Eduardo Pauluk
 */
class OperadoraCartao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Assert\NotBlank()
     * @Groups("operadoraCartao")
     */
    public ?string $descricao = null;

    /**
     * Em qual Carteira as movimentações desta Operadora acontecem.
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("operadoraCartao")
     */
    public ?Carteira $carteira = null;

}
