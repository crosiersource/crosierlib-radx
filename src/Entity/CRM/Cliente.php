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
     * @ORM\Column(name="documento", type="string")
     * @var null|string
     *
     * @Groups("entity")
     */
    public $documento;

    /**
     *
     * @ORM\Column(name="nome", type="string")
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


    /**
     * @param string $tipo
     * @return array|null
     */
    public function getEnderecoByTipo(string $tipo): ?array
    {
        $enderecos = $this->jsonData['enderecos'] ?? null;
        if ($enderecos) {
            foreach ($enderecos as $endereco) {
                if (strpos($endereco['tipo'], $tipo) !== FALSE) {
                    return $endereco;
                }
            }
        }
        return null;
    }

}
