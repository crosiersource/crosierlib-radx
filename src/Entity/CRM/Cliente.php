<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\CRM;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository")
 * @ORM\Table(name="crm_cliente")
 *
 * @author Carlos Eduardo Pauluk
 */
class Cliente implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="nome", type="string", nullable=true, length=200)
     * @var null|string
     *
     * @Groups("entity")
     */
    public $nome;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;

}
