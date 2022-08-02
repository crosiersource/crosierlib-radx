<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
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

/**
 * Entidade 'Carteira'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"carteira","operadoraCartao","entityId","user"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"carteira"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/carteira/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/carteira/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"},
 *          "delete"={"path"="/fin/carteira/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/carteira", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/carteira", "security"="is_granted('ROLE_FINAN_ADMIN')"}
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
 * @ApiFilter(BooleanFilter::class, properties={
 *     "atual": "exact",
 *     "caixa": "exact",
 *     "concreta": "exact",
 *     "abertas": "exact",
 *     "caixaStatus": "exact",
 *     "cheque": "exact"})
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "codigo": "exact",
 *     "descricao": "partial",
 *     "caixaResponsavel": "exact",
 *     "id": "exact",
 *     "operadoraCartao": "exact"})
 *
 * @ApiFilter(OrderFilter::class, properties={"id", "codigo", "descricao", "dtConsolidado", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CarteiraEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CarteiraRepository")
 * @ORM\Table(name="fin_carteira")
 *
 * @author Carlos Eduardo Pauluk
 */
class Carteira implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Groups("carteira")
     */
    public ?int $codigo = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("carteira")
     */
    public ?string $descricao = null;

    /**
     * Movimentações desta carteira não poderão ter suas datas alteradas para antes desta.
     *
     * @ORM\Column(name="dt_consolidado", type="datetime", nullable=false)
     * @Groups("carteira")
     */
    public ?DateTime $dtConsolidado = null;

    /**
     * Uma Carteira concreta é aquela em que podem ser efetuados créditos e
     * débitos (status 'REALIZADA'), como uma conta corrente ou um caixa.
     *
     * Um Grupo de Movimentação só pode estar vinculado à uma Carteira concreta.
     * Uma movimentação que contenha um grupo de movimentação, precisa ter sua
     * carteira igual a carteira do grupo de movimentação.
     *
     *
     * @ORM\Column(name="concreta", type="boolean", nullable=false)
     * @Groups("carteira")
     */
    public ?bool $concreta = false;

    /**
     * Informa se esta carteira pode conter movimentações com status ABERTA.
     *
     * @ORM\Column(name="abertas", type="boolean", nullable=false)
     * @Groups("carteira")
     */
    public ?bool $abertas = false;

    /**
     * Informa se esta carteira é um caixa (ex.: caixa a vista, caixa a prazo).
     *
     * @ORM\Column(name="caixa", type="boolean", nullable=false)
     * @Groups("carteira")
     */
    public ?bool $caixa = false;

    /**
     * ABERTO / FECHADO / null
     * @ORM\Column(name="caixa_status", type="string", nullable=true, length=20)
     * @Groups("carteira")
     * @var null|string
     */
    public ?string $caixaStatus = '';

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibBaseBundle\Entity\Security\User")
     * @ORM\JoinColumn(name="caixa_responsavel_id")
     * @MaxDepth(2)
     * @Groups("carteira")
     * @var User|null
     */
    public ?User $caixaResponsavel = null;

    /**
     * Informa se esta carteira possui talão de cheques.
     *
     * @ORM\Column(name="cheque", type="boolean", nullable=false)
     * @Groups("carteira")
     */
    public ?bool $cheque = false;

    /**
     * No caso da Carteira ser uma conta de banco, informa qual.
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco")
     * @ORM\JoinColumn(nullable=true)
     * @Groups("carteira")
     */
    public ?Banco $banco = null;

    /**
     * Código da agência (sem o dígito verificador).
     *
     * @ORM\Column(name="agencia", type="string", nullable=true, length=30)
     * @Groups("carteira")
     */
    public ?string $agencia = null;

    /**
     * Número da conta no banco (não segue um padrão).
     *
     * @ORM\Column(name="conta", type="string", nullable=true, length=30)
     * @Groups("carteira")
     */
    public ?string $conta = null;

    /**
     * Utilizado para informar o limite disponível.
     *
     * @ORM\Column(name="limite", type="decimal", nullable=true, precision=15, scale=2)
     *
     */
    public ?float $limite = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao")
     * @ORM\JoinColumn(name="operadora_cartao_id", nullable=true)
     *
     * @Groups("carteira")
     * @MaxDepth(1)
     */
    public ?OperadoraCartao $operadoraCartao = null;


    /**
     * Informa se esta carteira está atualmente em utilização.
     *
     * @ORM\Column(name="atual", type="boolean", nullable=false)
     * @Groups("carteira")
     */
    public ?bool $atual = false;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("carteira")
     */
    public ?array $jsonData = null;


    public function getCodigo(bool $format = false)
    {
        if ($format) {
            return str_pad($this->codigo, 3, '0', STR_PAD_LEFT);
        }

        return $this->codigo;
    }

    /**
     * @return string
     * @Groups("carteira")
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups({"carteira"})
     * @SerializedName("limite")
     * @return float
     */
    public function getLimiteFormatted(): float
    {
        return (float)$this->limite;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("carteira")
     * @SerializedName("limite")
     * @param float $limite
     */
    public function setLimiteFormatted(float $limite)
    {
        $this->limite = $limite;
    }


}
