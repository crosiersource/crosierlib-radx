<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;

/**
 * Repository para a entidade NotaFiscal.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalRepository extends FilterRepository
{

    private LoggerInterface $logger;

    private AppConfigEntityHandler $appConfigEntityHandler;

    /**
     * @required
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @required
     * @param AppConfigEntityHandler $appConfigEntityHandler
     */
    public function setAppConfigEntityHandler(AppConfigEntityHandler $appConfigEntityHandler): void
    {
        $this->appConfigEntityHandler = $appConfigEntityHandler;
    }


    public function getEntityClass(): string
    {
        return NotaFiscal::class;
    }


    /**
     *
     * @return int
     */
    public function findPrimeiroNSU(): int
    {
        try {
            $sql = 'SELECT min(nsu) as primeiro_nsu FROM fis_nf';
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('primeiro_nsu', 'primeiro_nsu');
            $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
            return $query->getOneOrNullResult()['primeiro_nsu'] ?? 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     *
     * @return int
     */
    public function findUltimoNSU(): int
    {
        try {
            $sql = 'SELECT max(nsu) as ultimo_nsu FROM fis_nf';
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('ultimo_nsu', 'ultimo_nsu');
            $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
            return $query->getOneOrNullResult()['ultimo_nsu'] ?? -1;
        } catch (NonUniqueResultException $e) {
            return -1;
        }
    }


    /**
     *
     * @return null|array
     */
    public function findAllNSUs(): ?array
    {
        $sql = 'SELECT nsu FROM fis_nf WHERE nsu IS NOT NULL ORDER BY nsu';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nsu', 'nsu');
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $result = $query->getResult();
        $ret = [];
        foreach ($result as $r) {
            $ret[] = intval($r['nsu']);
        }
        return $ret;
    }


    public function getDefaultOrders()
    {
        return array(
            'e.id' => 'desc',
            'e.dtEmissao' => 'desc'
        );
    }


    /**
     * Considera (arbitrariamente) como "nota não processada":
     *  - aquelas que tem o XML e a chave de acesso
     *  - mas não tem numero nem data de emissão.
     *  - E a pessoa emitente é diferente do emissor.
     *
     * @return array
     */
    public function findNotasNaoProcessadas(): array
    {
        $sql = 'SELECT id FROM fis_nf WHERE (xml_nota IS NOT NULL) AND (chave_acesso IS NOT NULL) AND (chave_acesso NOT LIKE \'%77498442000134%\')';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $ids = $query->getResult();
        $results = [];
        foreach ($ids as $id) {
            $results[] = $this->find($id);
        }
        return $results;
    }


    /**
     * Considera (arbitrariamente) como "nota não processada":
     *  - aquelas que tem o XML e a chave de acesso
     *  - mas não tem numero nem data de emissão.
     *  - E a pessoa emitente é diferente do emissor.
     *
     * @return array
     */
    public function findNotasComXMLMasSemChave(): array
    {
        $sql = 'SELECT id FROM fis_nf WHERE (xml_nota IS NOT NULL AND trim(xml_nota) != \'\') AND (chave_acesso IS NULL OR trim(chave_acesso) = \'\')';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $ids = $query->getResult();
        $results = [];
        foreach ($ids as $id) {
            $results[] = $this->find($id);
        }
        return $results;
    }


    /**
     * @param string $documento
     * @return array
     */
    public function findUltimosDadosPessoa(string $documento)
    {
        $p = [];
        try {
            $documento = preg_replace("/[^0-9]/", '', $documento);
            /** @var NotaFiscal $ultimo */
            $ultimo = $this->findByFiltersSimpl([['documentoDestinatario', 'EQ', $documento]], ['updated' => 'DESC']);
            $ultimo = $ultimo[0] ?? null;

            if ($ultimo) {
                $p['id'] = $ultimo->getId();
                $p['documento'] = $ultimo->getDocumentoDestinatario();
                $p['nome'] = $ultimo->getXNomeDestinatario();
                $p['ie'] = $ultimo->getInscricaoEstadualDestinatario();
            }
            /** @var NotaFiscal $ultimoComEndereco */
            $ultimoComEndereco = $this->findByFiltersSimpl([['documentoDestinatario', 'EQ', $documento], ['logradouroDestinatario', 'IS_NOT_NULL']], ['updated' => 'DESC']);
            $ultimoComEndereco = $ultimoComEndereco[0] ?? null;
            if ($ultimoComEndereco) {
                $p['logradouro'] = $ultimoComEndereco->getLogradouroDestinatario();
                $p['numero'] = $ultimoComEndereco->getNumeroDestinatario();
                $p['bairro'] = $ultimoComEndereco->getBairroDestinatario();
                $p['cidade'] = $ultimoComEndereco->getCidadeDestinatario();
                $p['estado'] = $ultimoComEndereco->getEstadoDestinatario();
                $p['cep'] = $ultimoComEndereco->getCepDestinatario();
                $p['fone'] = $ultimoComEndereco->getFoneDestinatario();
                $p['email'] = $ultimoComEndereco->getEmailDestinatario();
            } else {
                $p['logradouro'] = '';
                $p['numero'] = '';
                $p['complemento'] = '';
                $p['bairro'] = '';
                $p['cidade'] = '';
                $p['estado'] = '';
                $p['cep'] = '';
                $p['fone'] = '';
                $p['email'] = '';
            }
        } catch (ViewException $e) {
            $this->logger->error('Erro ao findUltimosDadosPessoa()');
        }

        return $p;

    }

}
