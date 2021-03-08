<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiResource;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Entidade 'Cadeia de Movimentações'.
 *
 * Movimentações podem ser dependentes umas das outras, formando uma cadeia de entradas e saídas entre carteiras.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/cadeia/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/cadeia/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/cadeia/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/cadeia", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/cadeia", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CadeiaEntityHandler")
 *
 * @ORM\Entity()
 * @ORM\Table(name="fin_cadeia")
 *
 * @author Carlos Eduardo Pauluk
 */
class Cadeia implements EntityId
{

    use EntityIdTrait;

    /**
     * Se for vinculante, ao deletar uma movimentação da cadeia todas deverão são deletadas (ver trigger trg_ad_delete_cadeia).
     *
     * @ORM\Column(name="vinculante", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $vinculate = false;

    /**
     * Se for fechada, não é possível incluir outras movimentações na cadeia.
     *
     * @ORM\Column(name="fechada", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $fechada = false;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Movimentacao",
     *      mappedBy="cadeia",
     *      orphanRemoval=true
     * )
     *
     * @var Movimentacao[]|ArrayCollection|null
     */
    public $movimentacoes;


    public function __construct()
    {
        $this->movimentacoes = new ArrayCollection();
    }


}

