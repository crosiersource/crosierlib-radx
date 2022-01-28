<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\CRM;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
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
        /** @var AppConfig $appConfig */
        $appConfig = $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                'chave' => 'crm_cliente_json_metadata'
            ]
        );
        return $appConfig->valor;
    }

    /**
     * Para sempre salvar os clientes pelo nome, mesmo quando não é informado um CPF.
     * Segue o padrão G99.999.999-99.
     *
     * @return string
     * @throws ViewException
     */
    public function findProxGDocumento()
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $rs = $conn->fetchAllAssociative('SELECT max(substr(documento,2,10)) as maxg FROM crm_cliente WHERE documento LIKE \'G%\'');
            if ($rs[0]['maxg'] ?? false) {
                return 'G' . str_pad(++$rs[0]['maxg'], 10, '0', STR_PAD_LEFT);
            } else {
                return 'G0000000001';
            }
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao pesquisar próximo documento (G)');
        }
    }

    /**
     * @return int
     * @throws ViewException
     */
    public function findProxCodCliente(): int
    {
        try {
            $sql = 'SELECT max(cod_cliente)+1 as prox FROM crm_clientes';
            $r = $this->getEntityManager()->getConnection()->fetchAssociative($sql);
            if ($r['prox'] ?: false) {
                return $r['prox'];
            }
            throw new \RuntimeException();
        } catch (\Throwable $e) {
            throw new ViewException('Não foi possível encontrar o próximo código de cliente');
        }
    }

    /**
     * Encontra os "clientes" cadastrados como FILIAL_PROP.
     * Esses clientes são as empresas que são gerenciadas pelo sistema.
     * Um dos locais onde isto é usado, é para setar como sacado ou cedente nas fin_movimentacao.
     *
     * @return null|array
     * @throws ViewException
     */
    public function findFiliaisProp(): ?array
    {
        try {
            $sql = 'SELECT id FROM crm_cliente WHERE json_data->>"$.filial_prop" = \'S\'';
            $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
            if (!$rs || count($rs) < 1) {
                return null;
            }
            $filiaisProp = [];
            foreach ($rs as $r) {
                $filiaisProp[] = $this->find($r['id']);
            }
            return $filiaisProp;
        } catch (\Throwable $e) {
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException('Erro ao pesquisar clientes FILIAL_PROP (' . $msg . ')', 0, $e);
        }
    }

}
