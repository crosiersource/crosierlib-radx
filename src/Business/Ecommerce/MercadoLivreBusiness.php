<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Ecommerce;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\PushMessageEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Ecommerce\IntegradorMercadoLivre;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Carlos Eduardo Pauluk
 */
class MercadoLivreBusiness
{

    private ClienteConfigEntityHandler $clienteConfigEntityHandler;

    private MercadoLivrePerguntaEntityHandler $mercadoLivrePerguntaEntityHandler;

    private MercadoLivreItemEntityHandler $mercadoLivreItemEntityHandler;

    private IntegradorMercadoLivre $integradorMercadoLivre;

    private SyslogBusiness $syslog;

    private PushMessageEntityHandler $pushMessageEntityHandler;


    public function __construct(ClienteConfigEntityHandler        $clienteConfigEntityHandler,
                                MercadoLivrePerguntaEntityHandler $mercadoLivrePerguntaEntityHandler,
                                MercadoLivreItemEntityHandler     $mercadoLivreItemEntityHandler,
                                IntegradorMercadoLivre            $integradorMercadoLivre,
                                SyslogBusiness                    $syslog,
                                PushMessageEntityHandler          $pushMessageEntityHandler
    )
    {
        $this->clienteConfigEntityHandler = $clienteConfigEntityHandler;
        $this->mercadoLivrePerguntaEntityHandler = $mercadoLivrePerguntaEntityHandler;
        $this->mercadoLivreItemEntityHandler = $mercadoLivreItemEntityHandler;
        $this->integradorMercadoLivre = $integradorMercadoLivre;
        $this->pushMessageEntityHandler = $pushMessageEntityHandler;
        $this->syslog = $syslog->setApp('conecta')->setComponent(self::class);
    }


    /**
     * @throws ViewException
     */
    private function saveAuthInfo(ClienteConfig $clienteConfig, int $i, array $authInfo)
    {
        try {
            $clienteConfig->jsonData['mercadolivre']['access_token'] = $authInfo['access_token'];
            $clienteConfig->jsonData['mercadolivre']['token_type'] = $authInfo['token_type'];
            $clienteConfig->jsonData['mercadolivre']['expires_in'] = $authInfo['expires_in'];
            $clienteConfig->jsonData['mercadolivre']['autorizado_em'] =
                (new \DateTime())->format('Y-m-d H:i:s');
            $clienteConfig->mercadolivreExpiraEm =
                (new \DateTime())->add(new \DateInterval('PT' . $authInfo['expires_in'] . 'S'));
            $clienteConfig->jsonData['mercadolivre']['scope'] = $authInfo['scope'];
            $clienteConfig->jsonData['mercadolivre']['refresh_token'] = $authInfo['refresh_token'];
            $this->clienteConfigEntityHandler->save($clienteConfig);
        } catch (\Exception $e) {
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException($msg, 0, $e);
        }
    }


    /**
     * @throws ViewException
     */
    public function autorizarApp(ClienteConfig $clienteConfig, int $i): void
    {
        $this->syslog->info('MercadoLivre.autorizarApp', $clienteConfig->jsonData['url_loja']);
        $r = $this->integradorMercadoLivre->autorizarApp(
            $clienteConfig->jsonData['mercadolivre'][$i]['token_tg']);
        $this->saveAuthInfo($clienteConfig, $i, $r);
    }


    /**
     * @throws ViewException
     */
    public function handleAccessToken(ClienteConfig $clienteConfig, int $i): ?string
    {
        if (!($clienteConfig->jsonData['mercadolivre']['token_tg'] ?? false)) {
            $this->syslog->info('Cliente não está vinculado ao ML (sem mercadolivre.token_tg)', json_encode($clienteConfig));
            return null;
        }

        // Aqui seria mais fácil pegar direto do $clienteConfig->mercadolivreExpiraEm, mas por algum
        // motivo ele não está ficando atualizado corretamente. 
        $autorizadoEm = DateTimeUtils::parseDateStr($clienteConfig->jsonData['mercadolivre']['autorizado_em']);
        $expiraEm =
            ($autorizadoEm)->add(new \DateInterval('PT' . $clienteConfig->jsonData['mercadolivre']['expires_in'] . 'S'));

        if ($expiraEm->format('YmdHis') !== $clienteConfig->mercadolivreExpiraEm->format('YmdHis')) {
            $clienteConfig->mercadolivreExpiraEm = $expiraEm;
            $this->clienteConfigEntityHandler->save($clienteConfig);
        }
        if (DateTimeUtils::diffInMinutes($expiraEm, new \DateTime()) < 60) {
            $this->syslog->info('MercadoLivre.renewAccessToken', $clienteConfig->jsonData['url_loja']);
            if (!($clienteConfig->jsonData['mercadolivre']['refresh_token'] ?? null)) {
                throw new ViewException('Impossível renovar sem mercadolivre.refresh_token');
            }
            $r = $this->integradorMercadoLivre->renewAccessToken(
                $clienteConfig->jsonData['mercadolivre']['refresh_token']);
            $this->saveAuthInfo($clienteConfig, $r);
        }
        if (!($clienteConfig->jsonData['mercadolivre']['me']['id'] ?? null)) {
            $rMe = $this->integradorMercadoLivre->getMe($clienteConfig->jsonData['mercadolivre']['access_token']);
            $clienteConfig->jsonData['mercadolivre']['me'] = $rMe;
            $this->clienteConfigEntityHandler->save($clienteConfig);
        }
        return $clienteConfig->jsonData['mercadolivre']['access_token'];
    }


    public function atualizar(): JsonResponse
    {
        $this->syslog->info('MercadoLivre.getQuestionsGlobal - INI');
        $clienteConfigs = $this->mercadoLivrePerguntaEntityHandler->getDoctrine()
            ->getRepository(ClienteConfig::class)->findByAtivo(true);

        $repoMlItem = $this->mercadoLivrePerguntaEntityHandler->getDoctrine()
            ->getRepository(MercadoLivreItem::class);

        $repoMlPergunta = $this->mercadoLivrePerguntaEntityHandler->getDoctrine()
            ->getRepository(MercadoLivrePergunta::class);

        /** @var ClienteConfig $clienteConfig */
        foreach ($clienteConfigs as $clienteConfig) {
            if ($clienteConfig->jsonData['mercadolivre']['access_token'] ?? false) {
                $q = 0;
                try {
                    $this->handleAccessToken($clienteConfig);
                    $offset = $clienteConfig->jsonData['mercadolivre']['questions_offset'] ?? 0;
                    $rs = $this->integradorMercadoLivre->getQuestions(
                        $clienteConfig->jsonData['mercadolivre']['access_token'],
                        $offset);
                    $this->syslog->info('MercadoLivre.getQuestionsGlobal - total de perguntas: ' . count($rs), $clienteConfig->jsonData['url_loja']);
                    $offset += count($rs);
                    $clienteConfig->jsonData['mercadolivre']['questions_offset'] = $offset;
                    $this->clienteConfigEntityHandler->save($clienteConfig);
                    foreach ($rs as $r) {
                        $pergunta = $repoMlPergunta->findOneByMercadolivreId($r['id']);
                        if ($pergunta) continue;
                        $item = $repoMlItem->findOneByMercadolivreId($r['item_id']);
                        if (!$item) {
                            $item = $this->getItem($clienteConfig, $r['item_id']);
                        }
                        $pergunta = new MercadoLivrePergunta();
                        $pergunta->mercadoLivreItem = $item;
                        $pergunta->mercadolivreId = $r['id'];
                        $pergunta->jsonData['r'] = $r;
                        $pergunta->status = $r['status'];
                        $pergunta->dtPergunta = DateTimeUtils::parseDateStr($r['date_created']);
                        $this->mercadoLivrePerguntaEntityHandler->save($pergunta);
                        $q++;
                    }
                } catch (ViewException $e) {
                    $this->syslog->err('Erro na iteração do MercadoLivreBusiness::atualizar para ' .
                        $clienteConfig->cliente->nome .
                        ' (' . $e->getMessage() . ')', $e->getTraceAsString());
                }
                if ($q) {
                    $this->pushMessageEntityHandler
                        ->enviarMensagemParaLista(
                            $q . " nova(s) pergunta(s) para " .
                            $clienteConfig->cliente->nome,
                            "MSGS_ML");
                }
            } else {
                $this->syslog->info('MercadoLivre.getQuestionsGlobal - access_token n/d', $clienteConfig->jsonData['url_loja']);
            }
        }

        return new JsonResponse(
            [
                'RESULT' => 'OK',
                'MSG' => 'Executado com sucesso',
            ]
        );
    }


    /**
     * @throws ViewException
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function getClienteConfigByUserId($userId): ?ClienteConfig
    {
        try {
            $conn = $this->clienteConfigEntityHandler->getDoctrine()->getConnection();
            $r = $conn->fetchAssociative('SELECT id FROM cnct_cliente_config WHERE json_data->>"$.mercadolivre.me.id" = :userId', ['userId' => $userId]);
            if ($r['id'] ?? null) {
                return $this->clienteConfigEntityHandler->getDoctrine()->getRepository(ClienteConfig::class)->find($r['id']);
            }
            throw new ViewException('Nenhum clienteConfig para este userId (' . $userId . ')');
        } catch (Exception $e) {
            throw new ViewException('Erro - getClienteConfigByUserId', 0, $e);
        }
    }


    /**
     * Handle chamado pelo MlNotificationHandler (que fica ouvindo as requisições enviadas pelo ML)
     *
     * @throws ViewException
     */
    public function handleMessage(string $resourceId, string $userId): void
    {
        $clienteConfig = $this->getClienteConfigByUserId($userId);
        $rs = $this->integradorMercadoLivre->getMessage(
            $clienteConfig->jsonData['mercadolivre']['access_token'],
            $resourceId
        );
    }

    /**
     * Handle chamado pelo MlNotificationHandler (que fica ouvindo as requisições enviadas pelo ML)
     *
     * @throws ViewException
     */
    public function handleQuestion(string $resourceId, string $userId): void
    {
        $clienteConfig = $this->getClienteConfigByUserId($userId);
        $this->handleAccessToken($clienteConfig);
        $r = $this->integradorMercadoLivre->getQuestion(
            $clienteConfig->jsonData['mercadolivre']['access_token'],
            $resourceId
        );


        $repoMlItem = $this->mercadoLivrePerguntaEntityHandler->getDoctrine()
            ->getRepository(MercadoLivreItem::class);

        $repoMlPergunta = $this->mercadoLivrePerguntaEntityHandler->getDoctrine()
            ->getRepository(MercadoLivrePergunta::class);

        $pergunta = $repoMlPergunta->findOneByMercadolivreId($r['id']);
        if ($pergunta) return;
        $item = $repoMlItem->findOneByMercadolivreId($r['item_id']);
        if (!$item) {
            $item = $this->getItem($clienteConfig, $r['item_id']);
        }
        $pergunta = new MercadoLivrePergunta();
        $pergunta->mercadoLivreItem = $item;
        $pergunta->mercadolivreId = $r['id'];
        $pergunta->jsonData['r'] = $r;
        $pergunta->status = $r['status'];
        $pergunta->dtPergunta = DateTimeUtils::parseDateStr($r['date_created']);
        $this->mercadoLivrePerguntaEntityHandler->save($pergunta);
    }


    /**
     * Handle chamado pelo MlNotificationHandler (que fica ouvindo as requisições enviadas pelo ML)
     *
     * @throws ViewException
     */
    public function handleClaim(string $resourceId, string $userId): void
    {
        $clienteConfig = $this->getClienteConfigByUserId($userId);
        $rs = $this->integradorMercadoLivre->getClaim(
            $clienteConfig->jsonData['mercadolivre']['access_token'],
            $resourceId
        );
    }


    /**
     * @throws ViewException
     */
    public function responder(MercadoLivrePergunta $pergunta, string $resposta)
    {
        $this->handleAccessToken($pergunta->mercadoLivreItem->clienteConfig);
        $rs = $this->integradorMercadoLivre->responder(
            $pergunta->mercadoLivreItem->clienteConfig->jsonData['mercadolivre']['access_token'],
            $pergunta->mercadolivreId,
            $resposta);
        if ($rs['status'] !== 'ANSWERED') {
            throw new ViewException('Erro ao responder pergunta');
        }
        $this->atualizarPergunta($pergunta);
    }

    /**
     * @throws ViewException
     */
    public function atualizarPergunta(MercadoLivrePergunta $pergunta)
    {
        $this->handleAccessToken($pergunta->mercadoLivreItem->clienteConfig);
        $rs = $this->integradorMercadoLivre->atualizarPergunta(
            $pergunta->mercadoLivreItem->clienteConfig->jsonData['mercadolivre']['access_token'],
            $pergunta->mercadolivreId);
        $pergunta->jsonData['r'] = $rs;
        $pergunta->status = $rs['status'];
        $this->mercadoLivrePerguntaEntityHandler->save($pergunta);
    }


    /** @noinspection PhpIncompatibleReturnTypeInspection */
    /**
     * @throws ViewException
     */
    public function getItem(ClienteConfig $clienteConfig, string $id): MercadoLivreItem
    {
        $rs = $this->integradorMercadoLivre->getItem($clienteConfig->jsonData['mercadolivre']['access_token'], $id);

        if (($rs['error'] ?? '') === 'not_found') {
            $rs['title'] = 'NÃO ENCONTRADO';
            $rs['price'] = 0;
        }
        $item = new MercadoLivreItem();
        $item->clienteConfig = $clienteConfig;
        $item->descricao = $rs['title'];
        $item->precoVenda = $rs['price'];
        $item->mercadolivreId = $id;
        $item->jsonData['r'] = $rs;

        return $this->mercadoLivreItemEntityHandler->save($item);
    }

}