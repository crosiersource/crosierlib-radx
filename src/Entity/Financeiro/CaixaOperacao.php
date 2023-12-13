<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Entity\Security\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade 'CaixaOperacao'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"caixaOperacao","carteira","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"caixaOperacao"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/caixaOperacao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/caixaOperacao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/caixaOperacao/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/caixaOperacao", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/caixaOperacao", "security"="is_granted('ROLE_FINAN')"}
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
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "carteira": "exact",
 *     "responsavel.nome": "partial",
 *     "id": "exact",
 *     "operacao": "exact",
 *     "dtOperacao": "exact"
 * })
 *
 * @ApiFilter(OrderFilter::class, properties={"id", "carteira.descricao", "operacao", "dtOperacao", "responsavel.nome"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CaixaOperacaoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CaixaOperacaoRepository")
 * @ORM\Table(name="fin_caixa_operacao")
 *
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacao implements EntityId
{

    use EntityIdTrait;


    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("procedimento")
     * @Assert\Length(min=36, max=36)
     *
     * @var string|null
     */
    public ?string $UUID = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id")
     * @Groups("caixaOperacao")
     * @MaxDepth(2)
     * @var Carteira|null
     */
    public ?Carteira $carteira = null;


    /**
     * ABERTURA / FECHAMENTO / CONFERÊNCIA
     * @ORM\Column(name="operacao", type="string", nullable=false, length=20)
     * @Groups("caixaOperacao")
     * @var null|string
     */
    public ?string $operacao = null;

    /**
     * @ORM\Column(name="obs", type="string", nullable=true, length=255)
     * @Groups("caixaOperacao")
     * @var null|string
     */
    public ?string $obs = null;


    /**
     * @ORM\Column(name="dt_operacao", type="datetime", nullable=false)
     * @Groups("caixaOperacao")
     * @Assert\Type("\DateTime")
     * @Assert\NotNull()
     *
     * @var DateTime|null
     */
    public ?DateTime $dtOperacao = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibBaseBundle\Entity\Security\User")
     * @ORM\JoinColumn(name="responsavel_id")
     * @MaxDepth(2)
     * @Groups("caixaOperacao")
     * @var User|null
     */
    public ?User $responsavel = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibBaseBundle\Entity\Security\User")
     * @ORM\JoinColumn(name="responsavel_dest_id")
     * @MaxDepth(2)
     * @Groups("caixaOperacao")
     * @var User|null
     */
    public ?User $responsavelDest = null;


    /**
     * @ORM\Column(name="valor", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     * @var null|string
     */
    public ?string $valor = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @NotUppercase()
     * @Groups("caixaOperacao")
     *
     * @var null|array
     */
    public ?array $jsonData = null;


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("caixaOperacao")
     * @SerializedName("valor")
     * @return float
     */
    public function getValorFormatted(): float
    {
        return (float)$this->valor;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("caixaOperacao")
     * @SerializedName("valor")
     * @param float $valor
     */
    public function setValorFormatted(float $valor)
    {
        $this->valor = $valor;
    }

    /**
     * @Groups("caixaOperacao")
     */
    public function getStatus(): string
    {
        if ($this->operacao === 'ABERTURA') {
            return 'ABERTO';
        }
        return 'FECHADO';
    }


}
