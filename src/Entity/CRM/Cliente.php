<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\CRM;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
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
     * @ORM\Column(name="documento", type="string")
     * @Groups("entity")
     */
    public ?string $documento;

    /**
     * @ORM\Column(name="nome", type="string")
     * @Groups("entity")
     */
    public ?string $nome;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


    /**
     * @return string
     * @Groups("entity")
     */
    public function getNomeMontadoComDocumento(): string
    {
        $r = StringUtils::mascararCnpjCpf($this->documento) . ' - ';
        if ($this->jsonData['nomeFantasia'] ?? false) {
            $r .= $this->nome . ' (' . $this->jsonData['nomeFantasia'] . ')';
        } else {
            $r .= $this->nome;
        }
        return $r;
    }


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

    /**
     * Insere somente se já não existir.
     *
     * @param array $novoEndereco
     */
    public function inserirNovoEndereco(array $novoEndereco)
    {
        // Verifica os endereços do cliente
        $enderecoJaSalvo = false;
        if (($this->jsonData['enderecos'] ?? false) && count($this->jsonData['enderecos']) > 0) {
            foreach ($this->jsonData['enderecos'] as $endereco) {
                if (
                    (($endereco['logradouro'] ?? '') === ($novoEndereco['logradouro'] ?? '')) &&
                    (($endereco['numero'] ?? '') === ($novoEndereco['numero'] ?? '')) &&
                    (($endereco['complemento'] ?? '') === ($novoEndereco['complemento'] ?? '')) &&
                    (($endereco['bairro'] ?? '') === ($novoEndereco['bairro'] ?? '')) &&
                    (($endereco['cep'] ?? '') === ($novoEndereco['cep'] ?? '')) &&
                    (($endereco['cidade'] ?? '') === ($novoEndereco['cidade'] ?? '')) &&
                    (($endereco['estado'] ?? '') === ($novoEndereco['estado'] ?? ''))) {
                    $enderecoJaSalvo = true;
                }
            }
        }
        if (!$enderecoJaSalvo) {
            if (!isset($this->jsonData['enderecos'])) {
                $this->jsonData['enderecos'] = [];
            }
            $this->jsonData['enderecos'][] = [
                'tipo' => $novoEndereco['tipo'] ?? '',
                'logradouro' => $novoEndereco['logradouro'] ?? '',
                'numero' => $novoEndereco['numero'] ?? '',
                'complemento' => $novoEndereco['complemento'] ?? '',
                'bairro' => $novoEndereco['bairro'] ?? '',
                'cep' => $novoEndereco['cep'] ?? '',
                'cidade' => $novoEndereco['cidade'] ?? '',
                'estado' => $novoEndereco['estado'] ?? '',
            ];
        }
    }

}
