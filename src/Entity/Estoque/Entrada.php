<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\EntradaRepository")
 * @ORM\Table(name="est_entrada")
 *
 * @author Carlos Eduardo Pauluk
 */
class Entrada implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="dt_lote", type="datetime")
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtLote = null;

    
    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $descricao = null;


    /**
     *
     * @ORM\Column(name="responsavel", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $responsavel = null;


    /**
     *
     * @ORM\Column(name="status", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $status = null;


    /**
     *
     * @ORM\Column(name="dt_integracao", type="datetime")
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtIntegracao = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;

    /**
     *
     * @var null|EntradaItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="EntradaItem",
     *      cascade={"persist"},
     *      mappedBy="entrada",
     *      orphanRemoval=true)
     * @Groups("entity")
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }

    public function addItem(?EntradaItem $item): void
    {
        if (!$this->itens->contains($item)) {
            $this->itens->add($item);
        }
    }
}
    