<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade Modo de Movimentação.
 *
 * Informa se a movimentação foi em 'espécie', 'cheque', 'boleto', etc.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"modo","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"modo"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/modo", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/modo", "security"="is_granted('ROLE_FINAN')"}
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
 * @ApiFilter(SearchFilter::class, properties={"descricao": "partial"})
 * @ApiFilter(NumericFilter::class, properties={"id","codigo"})
 * @ApiFilter(OrderFilter::class, properties={"id", "codigo", "descricao", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\ModoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ModoRepository")
 * @ORM\Table(name="fin_modo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Modo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer")
     * @Groups("modo")
     */
    public ?int $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("modo")
     */
    public ?string $descricao = null;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="transf_propria", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("modo")
     */
    public ?bool $modoDeTransfPropria = false;

    /**
     *
     * @ORM\Column(name="moviment_agrup", type="boolean")
     * @Assert\NotNull()
     * @Groups("modo")
     */
    public ?bool $modoDeMovimentAgrup = false;

    /**
     *
     * @ORM\Column(name="modo_cartao", type="boolean")
     * @Assert\NotNull()
     * @Groups("modo")
     */
    public ?bool $modoDeCartao = false;

    /**
     *
     * @ORM\Column(name="modo_cheque", type="boolean")
     * @Groups("modo")
     */
    public ?bool $modoDeCheque = false;

    /**
     * Informa se este modo é aceito para transferência/recolhimento de caixas.
     *
     * @ORM\Column(name="transf_caixa", type="boolean")
     * @Assert\NotNull()
     * @Groups("modo")
     */
    public ?bool $modoDeTransfCaixa = false;

    /**
     *
     * @ORM\Column(name="com_banco_origem", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("modo")
     *
     * @var bool|null
     */
    public ?bool $modoComBancoOrigem = false;


    /**
     * @param bool $format
     * @return int|null|string
     */
    public function getCodigo($format = false)
    {
        if ($format) {
            return str_pad($this->codigo, 2, "0", STR_PAD_LEFT);
        }
        return $this->codigo;

    }

    /**
     * @Groups("modo")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


}
