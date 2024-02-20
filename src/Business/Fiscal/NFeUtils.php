<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Tools as ToolsCommon;
use NFePHP\NFe\Tools;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Security\Core\Security;

/**
 * @author Carlos Eduardo Pauluk
 */
class NFeUtils
{

    public Connection $conn;

    private SyslogBusiness $logger;

    private AppConfigEntityHandler $appConfigEntityHandler;

    public Security $security;


    /**
     * NFeUtils constructor.
     * @param Connection $conn
     * @param SyslogBusiness $logger
     * @param AppConfigEntityHandler $appConfigEntityHandler
     * @param Security $security
     */
    public function __construct(
        Connection             $conn,
        SyslogBusiness         $logger,
        AppConfigEntityHandler $appConfigEntityHandler,
        Security               $security
    )
    {
        $this->conn = $conn;
        $this->logger = $logger->setApp('radx')->setComponent(self::class)->setEcho(false);
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->security = $security;
    }


    /**
     * @throws ViewException
     */
    public function clearCaches(): void
    {
        try {
            $cache = new FilesystemAdapter($_SERVER['CROSIERAPP_ID'] . '.cache', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);
            $cache->delete('nfeTools_configs');
            $cache->delete('nfeConfigs');
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao limpar nfeTools e nfeConfigs do cachê');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao limpar nfeTools e nfeConfigs do cachê');
        }
    }


    /**
     * @param array $configs
     * @throws ViewException
     */
    public function saveNFeConfigs(array $configs): void
    {
        try {
            $cache = new FilesystemAdapter($_SERVER['CROSIERAPP_ID'] . '.cache', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);
            $cache->deleteItem('nfeTools_configs');
            $cache->deleteItem('nfeConfigs');
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao limpar nfeTools e nfeConfigs do cachê');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao limpar nfeTools e nfeConfigs do cachê');
        }

        // Verifica qual nfeConfigs está em uso no momento
        $idNfeConfigsEmUso = $this->getNfeConfigsIdEmUso();

        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
        /** @var AppConfig $appConfig */
        $appConfig = $repoAppConfig->find($idNfeConfigsEmUso);

        $configsSaved = json_decode($appConfig->valor, true);
        $configs['certificado'] = $configs['certificado'] ?? $configsSaved['certificado'];
        $configs['certificadoPwd'] = $configs['certificadoPwd'] ?? $configsSaved['certificadoPwd'];
        $configs['atualizacao'] = $configs['atualizacao']->format('Y-m-d H:i:s.u');

        $appConfig->chave = 'nfeConfigs_' . $configs['cnpj'];
        $appConfig->appUUID = $_SERVER['CROSIERAPPRADX_UUID'];
        $appConfig->valor = json_encode($configs);
        $this->appConfigEntityHandler->save($appConfig);
    }


    /**
     * Retorna o id do cfg_app_config que contém as nfeConfigs setadas como 'em uso' para o usuário logado.
     *
     * @return mixed
     * @throws ViewException
     */
    public function getNfeConfigsIdEmUso(): int
    {
        if (!isset($_SERVER['CROSIERAPPRADX_UUID'])) {
            throw new ViewException('CROSIERAPPRADX_UUID n/d');
        }
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);

        $username = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'INDEFINIDO';
        /** @var AppConfig $appConfig_nfeConfigsIdEmUso */
        $appConfig_nfeConfigsIdEmUso = $repoAppConfig->findOneBy(['appUUID' => $_SERVER['CROSIERAPPRADX_UUID'], 'chave' => 'nfeConfigsIdEmUso_' . $username]);
        if ($appConfig_nfeConfigsIdEmUso) {
            return (int)$appConfig_nfeConfigsIdEmUso->valor;
        } else {
            $appConfig_nfeConfigsIdEmUso_padrao = $repoAppConfig->findOneBy(['appUUID' => $_SERVER['CROSIERAPPRADX_UUID'], 'chave' => 'nfeConfigsIdEmUso_padrao']);
            if (!$appConfig_nfeConfigsIdEmUso_padrao) {
                $appConfig_nfeConfigsIdEmUso_padrao = $repoAppConfig->findByFiltersSimpl(
                    [
                        ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']],
                        ['chave', 'LIKE', 'nfeConfigs_%']
                    ]);
                if (!$appConfig_nfeConfigsIdEmUso_padrao) {
                    throw new ViewException('Nenhuma nfeConfigs encontrada');
                }
                $appConfig_nfeConfigsIdEmUso_padrao = $appConfig_nfeConfigsIdEmUso_padrao[0];
            }
            $appConfig_nfeConfigsIdEmUso = new AppConfig();
            $appConfig_nfeConfigsIdEmUso->chave = 'nfeConfigsIdEmUso_' . $username;
            $appConfig_nfeConfigsIdEmUso->appUUID = $_SERVER['CROSIERAPPRADX_UUID'];
            $appConfig_nfeConfigsIdEmUso->valor = $appConfig_nfeConfigsIdEmUso_padrao->valor;
            $this->appConfigEntityHandler->save($appConfig_nfeConfigsIdEmUso);
        }
        return (int)$appConfig_nfeConfigsIdEmUso->valor;
    }


    /**
     * @param int $id
     * @throws ViewException
     */
    public function saveNfeConfigsIdEmUso(int $id): void
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
        $username = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'INDEFINIDO';
        /** @var AppConfig $appConfig_nfeConfigsIdEmUso */
        $appConfig_nfeConfigsIdEmUso = $repoAppConfig->findOneBy(['appUUID' => $_SERVER['CROSIERAPPRADX_UUID'], 'chave' => 'nfeConfigsIdEmUso_' . $username]);
        $appConfig_nfeConfigsIdEmUso->setValor($id);
        $this->appConfigEntityHandler->save($appConfig_nfeConfigsIdEmUso);
    }


    /**
     * Retorna o Tools a partir do nfeConfigs em uso.
     *
     * @return Tools
     * @throws ViewException
     */
    public function getToolsEmUso(): Tools
    {
        try {
            // Verifica qual nfeConfigs está em uso no momento
            $idNfeConfigsEmUso = $this->getNfeConfigsIdEmUso();
            return $this->getTools($idNfeConfigsEmUso);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter tools do cachê');
            $this->logger->error($e->getMessage());
            if ($e instanceof ViewException) {
                throw $e;
            }
            // else
            throw new ViewException('Erro ao obter tools do cachê');
        }
    }


    /**
     * Retorna o Tools a partir do nfeConfigs de um CNPJ específico.
     * @throws ViewException
     */
    public function getToolsByCNPJ(string $cnpj, ?bool $ctes = false)
    {
        try {
            $idNfeConfigs = $this->getNFeConfigsByCNPJ($cnpj);
            return $this->getTools($idNfeConfigs['id'], $ctes);
        } catch (\Throwable $e) {
            $erro = "Erro ao obter tools por cnpj: $cnpj (CTEs: " . ($ctes ? 'SIM' : 'NÃO') . '). ';
            $erro .= $e->getMessage();
            $this->logger->error($erro);
            $this->logger->error($e->getMessage());
            throw new ViewException($erro);
        }
    }

    /**
     * @throws ViewException
     */
    private function getTools(int $idNfeConfigs, ?bool $ctes = false)
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
        /** @var AppConfig $appConfig */
        $appConfig = $repoAppConfig->find($idNfeConfigs);

        $configs = json_decode($appConfig->valor, true);
        if ($configs['tpAmb'] === 1) {
            $configs['CSC'] = $configs['CSC_prod'];
            $configs['CSCid'] = $configs['CSCid_prod'];
        } else {
            $configs['CSC'] = $configs['CSC_hom'];
            $configs['CSCid'] = $configs['CSCid_hom'];
        }

        $pfx = base64_decode($configs['certificado']);
        if (!$pfx) {
            throw new ViewException('Certificado não encontrado');
        }
        $pwd = $configs['certificadoPwd'];
        try {
            $certificate = Certificate::readPfx($pfx, $pwd);
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao ler certificado');
        }
        if ($ctes) {
            return new \NFePHP\CTe\Tools(json_encode($configs), $certificate);
        } else {
            return new Tools(json_encode($configs), $certificate);
        }
    }


    /**
     * Chamada para pegar informações do CNPJ, Razão Social, etc.
     * Não retorna o certificado nem a senha, pois... ?
     *
     * @param string $cnpj
     * @return array
     * @throws ViewException
     */
    public function getNFeConfigsByCNPJ(string $cnpj): array
    {
        try {
            $nfeConfigsJSON = $this->conn->fetchAssociative('SELECT id, valor FROM cfg_app_config WHERE app_uuid = :appUUID AND chave = :chave',
                ['appUUID' => '9121ea11-dc5d-4a22-9596-187f5452f95a', 'chave' => 'nfeConfigs_' . $cnpj]);
            if (!$nfeConfigsJSON) {
                throw new ViewException('Nenhum nfeConfigs encontrado para o CNPJ ' . $cnpj);
            }
            $a = json_decode($nfeConfigsJSON['valor'], true);
            $a['atualizacao'] = isset($a['atualizacao']) ? DateTimeUtils::parseDateStr($a['atualizacao']) : '';
            $a['id'] = $nfeConfigsJSON['id'];
            $a['razaosocial'] = strtoupper($a['razaosocial']);
            $a['enderEmit_xLgr'] = strtoupper($a['enderEmit_xLgr']);
            $a['enderEmit_xBairro'] = strtoupper($a['enderEmit_xBairro']);
            unset($a['certificado'], $a['certificadoPwd']);
            return $a;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao obter nfeConfigs do cachê (NfeUtils)');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao obter nfeConfigs do cachê');
        }
    }


    /**
     * Chamada para pegar informações do CNPJ, Razão Social, etc.
     * Não retorna o certificado nem a senha.
     *
     * @return array
     * @throws ViewException
     */
    public function getNFeConfigsEmUso(): array
    {
        try {
            $nfeConfigsJSON = $this->conn->fetchAssociative('SELECT id, valor FROM cfg_app_config WHERE id = :id',
                ['id' => $this->getNfeConfigsIdEmUso()]);
            $a = json_decode($nfeConfigsJSON['valor'], true);
            $a['atualizacao'] = isset($a['atualizacao']) ? DateTimeUtils::parseDateStr($a['atualizacao']) : '';
            $a['id'] = $nfeConfigsJSON['id'];
            $a['razaosocial'] = strtoupper($a['razaosocial']);
            $a['enderEmit_xLgr'] = strtoupper($a['enderEmit_xLgr']);
            $a['enderEmit_xBairro'] = strtoupper($a['enderEmit_xBairro']);
            unset($a['certificado'], $a['certificadoPwd']);
            return $a;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao obter nfeConfigs do cachê');
            $this->logger->error($e->getMessage());
            if ($e instanceof ViewException) {
                $msg = $e->getMessage();
            } else {
                $msg = 'Erro ao obter nfeConfigs do cachê';
            }
            throw new ViewException($msg);
        }
    }


    /**
     * Retorna todos os CNPJs configurados em entradas nfeConfigs_% na cfg_app_config.
     * @return array
     * @throws ViewException
     */
    public function getNFeConfigsCNPJs(): array
    {
        try {
            $nfeConfigs = $this->conn->fetchAllAssociative('SELECT id, valor FROM cfg_app_config WHERE app_uuid = :appUUID AND chave LIKE :chave',
                ['appUUID' => '9121ea11-dc5d-4a22-9596-187f5452f95a', 'chave' => 'nfeConfigs_%']);
            $cnpjs = [];
            foreach ($nfeConfigs as $nfeConfig) {
                $nfeConfigDecoded = json_decode($nfeConfig['valor'], true);
                if ($nfeConfigDecoded['cnpj'] ?? null) {
                    $cnpjs[] = $nfeConfigDecoded['cnpj'];
                }
            }
            return $cnpjs;
        } catch (Exception $e) {
            throw new ViewException('Erro ao pesquisar CNPJs em entradas nfeConfigs_%');
        }
    }


}
