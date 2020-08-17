<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\CRM;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ClienteRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Cliente::class;
    }

    /**
     * @return mixed
     */
    public function getJsonMetadata()
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->getEntityManager()->getRepository(AppConfig::class);
        return $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                'chave' => 'crm_cliente_json_metadata'
            ]
        )->getValor();
    }

    public function findProxGDocumento()
    {
        $conn = $this->getEntityManager()->getConnection();
        $rs = $conn->fetchAll('SELECT max(substr(documento,2,10)) as maxg FROM crm_cliente WHERE documento LIKE \'G%\'');
        if ($rs[0]['maxg'] ?? false) {
            return 'G' . str_pad(++$rs[0]['maxg'], 10, '0', STR_PAD_LEFT);
        } else {
            return 'G0000000001';
        }
    }

}
