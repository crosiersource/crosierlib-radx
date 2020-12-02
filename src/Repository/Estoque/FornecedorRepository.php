<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class FornecedorRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Fornecedor::class;
    }

    public function getJsonMetadata()
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->getEntityManager()->getRepository(AppConfig::class);
        return $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                'chave' => 'est_fornecedor_json_metadata'
            ]
        )->getValor();
    }

    /**
     * Encontra os "fornecedores" cadastrados como FILIAL_PROP.
     * Esses fornecedores são as empresas que são gerenciadas pelo sistema.
     * Um dos locais onde isto é usado, é para setar como sacado ou cedente nas fin_movimentacao.
     *
     * @return null|array
     * @throws ViewException
     */
    public function findFiliaisProp(): ?array
    {
        try {
            $sql = 'SELECT id FROM est_fornecedor WHERE json_data->>"$.filial_prop" = \'S\'';
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
