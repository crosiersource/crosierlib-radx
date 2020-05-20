<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\RH;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
use Doctrine\DBAL\Connection;

/**
 * Repository para a entidade Funcionario.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ColaboradorRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Colaborador::class;
    }


    public function getJsonMetadata()
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->getEntityManager()->getRepository(AppConfig::class);
        return $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                'chave' => 'rh_colaborador_json_metadata'
            ]
        )->getValor();
    }

    public function getVendedores(?bool $atuais = null)
    {
        $sql = 'SELECT id FROM rh_colaborador WHERE json_data->>"$.cargo" = :cargo';
        $params = ['cargo' => 'VENDEDOR'];
        if ($atuais !== NULL) {
            $sql .= $atuais !== NULL ? (' AND atual IS ' . ($atuais ? 'TRUE' : 'FALSE')) : '';
        }
        /** @var Connection $conn */
        $conn = $this->getEntityManager()->getConnection();
        $rs = $conn->fetchAll($sql, $params);
        $result = [];
        foreach ($rs as $r) {
            $result[] = $this->find($r['id']);
        }
        return $result;
    }


}
