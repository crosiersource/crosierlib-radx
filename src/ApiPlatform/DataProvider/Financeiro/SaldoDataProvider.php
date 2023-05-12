<?php

namespace CrosierSource\CrosierLibRadxBundle\ApiPlatform\DataProvider\Financeiro;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Clinica\Especialidade;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\SaldoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\SaldoRepository;
use Doctrine\DBAL\Connection;

/**
 * @author Carlos Eduardo Pauluk
 */
class SaldoDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{

    public SaldoEntityHandler $saldoEntityHandler;
    
    private SaldoRepository $repoSaldo;

    public Connection $conn;

    private array $saldosByData = [];

    private \DateTime $dtIni;

    private \DateTime $dtFim;

    private \DateTime $dtConsolidado;

    private Carteira $carteira;


    public function __construct(SaldoEntityHandler $saldoEntityHandler)
    {
        $this->saldoEntityHandler = $saldoEntityHandler;
        $this->repoSaldo = $this->saldoEntityHandler->getDoctrine()->getRepository(Saldo::class);
        $this->conn = $this->saldoEntityHandler->getDoctrine()->getConnection();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Saldo::class === $resourceClass &&
            ($context['filters']['carteira'] ?? null) &&
            ($context['filters']['dtSaldo'] ?? null);
    }

    /**
     * @throws ViewException
     */
    public function getCollection(
        string $resourceClass,
        string $operationName = null,
        array  $context = []
    ): iterable
    {
        $this->handleParams($context);
        $this->verificaSaldosParaCadaDia();
        return $this->getSaldos();
    }

    private function handleParams(array $context): void
    {
        $saldos = [];

        if (!is_array($context['filters']['dtSaldo'])) {
            $dtSaldo = $context['filters']['dtSaldo'];
            $context['filters']['dtSaldo'] = ['after' => $dtSaldo, 'before' => $dtSaldo];
        }
        $this->dtIni = DateTimeUtils::parseDateStr($context['filters']['dtSaldo']['after']);
        $this->dtFim = DateTimeUtils::parseDateStr($context['filters']['dtSaldo']['before']);
        if (DateTimeUtils::dataMaiorQue($this->dtIni, $this->dtFim)) {
            throw new \Exception('dtIni > dtFim');
        }

        $carteiraId = substr($context['filters']['carteira'], strrpos($context['filters']['carteira'], '/') + 1);
        $repoCarteira = $this->saldoEntityHandler->getDoctrine()->getRepository(Carteira::class);
        $this->carteira = $repoCarteira->find($carteiraId);

        $rDtConsolidado = $this->conn->fetchAssociative('SELECT dt_consolidado FROM fin_carteira WHERE id = :carteiraId', ['carteiraId' => $carteiraId]);
        $this->dtConsolidado = DateTimeUtils::parseDateStr($rDtConsolidado['dt_consolidado'] ?? '1900-01-01');
    }


    private function verificaSaldosParaCadaDia(): void
    {
        $todosOsDias = DateTimeUtils::getDatesList($this->dtIni, $this->dtFim);
        $this->buildSaldosByData();

        foreach ($todosOsDias as $dia) {
            $tem = $this->saldosByData[$dia->format('Y-m-d')] ?? null;

            if (!$tem || DateTimeUtils::dataMaiorQue($dia, $this->dtConsolidado)) {
                $this->calculaSaldoPara($dia);
            }
        }
    }

    private function buildSaldosByData(): void
    {
        $saldos = $this->conn->fetchAllAssociative(
            'SELECT id, dt_saldo, total_realizadas, total_pendencias ' .
            'FROM fin_saldo ' .
            'WHERE ' .
            'carteira_id = :carteiraId AND ' .
            'dt_saldo BETWEEN :dtIni AND :dtFim ' .
            'ORDER BY dt_saldo',
            [
                'carteiraId' => $this->carteira->getId(),
                'dtIni' => $this->dtIni->format('Y-m-d'),
                'dtFim' => $this->dtFim->format('Y-m-d'),
            ]);

        $this->saldosByData = [];
        foreach ($saldos as $saldo) {
            $this->saldosByData[$saldo['dt_saldo']] = $saldo;
        }
    }


    private function calculaSaldoPara(\DateTime $dia): void
    {
        /** @var MovimentacaoRepository $movimentacaoRepo */
        $movimentacaoRepo = $this->saldoEntityHandler->getDoctrine()->getRepository(Movimentacao::class);
        $saldoPosterior = (float)$movimentacaoRepo->findSaldo($dia, $this->carteira->getId(), 'SALDO_POSTERIOR_REALIZADAS') ?? 0;
        $saldoPosteriorComCheques = (float)$movimentacaoRepo->findSaldo($dia, $this->carteira->getId(), 'SALDO_POSTERIOR_COM_CHEQUES') ?? 0;

        // Só salva se mudou algo
        if (!isset($this->saldosByData[$dia->format('Y-m-d')]) ||
            (float)$this->saldosByData[$dia->format('Y-m-d')]['total_realizadas'] !== $saldoPosterior ||
            (float)$this->saldosByData[$dia->format('Y-m-d')]['total_pendencias'] !== $saldoPosteriorComCheques) {

            if (!isset($this->saldosByData[$dia->format('Y-m-d')])) {
                $saldo = new Saldo();
            } else {
                $saldo = $this->repoSaldo->find($this->saldosByData[$dia->format('Y-m-d')]['id']);
            }

            $saldo->carteira = $this->carteira;
            $saldo->dtSaldo = $dia;
            $saldo->totalRealizadas = $saldoPosterior;
            $saldo->totalPendencias = $saldoPosteriorComCheques;
            $this->saldoEntityHandler->save($saldo);
        }
    }


    private function getSaldos(): array
    {
        return $this->repoSaldo->findByFiltersSimpl([
            ['carteira', 'EQ', $this->carteira],
            ['dtSaldo', 'BETWEEN_DATE', [$this->dtIni, $this->dtFim]]
        ], ['dtSaldo' => 'ASC'], 0, null);
    }


}
