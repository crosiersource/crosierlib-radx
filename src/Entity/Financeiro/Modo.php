<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
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
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/modo/{id}", "security"="is_granted('ROLE_ADMIN')"}
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
 *
 * @ApiFilter(SearchFilter::class, properties={"descricao": "partial", "id": "exact", "codigo": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "updated"}, arguments={"orderParameterName"="order"})
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
     * @Groups("entity")
     */
    public ?int $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="transf_propria", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeTransfPropria = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="moviment_agrup", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeMovimentAgrup = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="modo_cartao", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeCartao = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="modo_cheque", type="boolean")
     * @Groups("entity")
     */
    public ?bool $modoDeCheque = false;

    /**
     * Informa se este modo é aceito para transferência/recolhimento de caixas.
     *
     * @ORM\Column(name="transf_caixa", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeTransfCaixa = false;

    /**
     *
     * @ORM\Column(name="com_banco_origem", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
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
     * @Groups("entity")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


}
