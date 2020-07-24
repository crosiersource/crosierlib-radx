<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\UnidadeRepository")
 * @ORM\Table(name="est_unidade")
 *
 * @author Carlos Eduardo Pauluk
 */
class Unidade implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="label", type="string")
     * @Groups("entity")
     * @NotUppercase()
     * @var null|string
     */
    public ?string $label = null;

    /**
     *
     * @ORM\Column(name="casas_decimais", type="integer")
     * @Groups("entity")
     * @var null|integer
     */
    public ?int $casasDecimais = null;

    /**
     *
     * @ORM\Column(name="json_info", type="string")
     * @var null|string
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?string $jsonInfo = null;

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
     * @ORM\Column(name="atual", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $atual = false;


}