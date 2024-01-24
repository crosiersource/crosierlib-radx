<?php

namespace CrosierSource\CrosierLibRadxBundle\Controller\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\EntityIdUtils\EntityIdUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\SaldoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\SaldoRepository;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller interno do bundle com métodos/rotas que devem estar disponíveis em quaisquer apps.
 *
 * @author Carlos Eduardo Pauluk
 */
class SaldoController extends AbstractController
{

    public SaldoEntityHandler $saldoEntityHandler;

    private SaldoRepository $repoSaldo;

    public Connection $conn;

    private array $saldosByData = [];

    private \DateTime $dtIni;

    private \DateTime $dtFim;

    private \DateTime $dtConsolidado;

    private Carteira $carteira;

    public function __construct(
        ContainerInterface $container,
        SaldoEntityHandler $saldoEntityHandler
    )
    {
        $this->container = $container;
        $this->saldoEntityHandler = $saldoEntityHandler;
        $this->repoSaldo = $this->saldoEntityHandler->getDoctrine()->getRepository(Saldo::class);
        $this->conn = $this->saldoEntityHandler->getDoctrine()->getConnection();
    }


    public function getSaldos(Request $request): JsonResponse
    {
        $dtSaldo = $request->get('dtSaldo');
        $carteiraUri = $request->get('carteira');
        $this->handleParams($dtSaldo, $carteiraUri);
        $this->verificaSaldosParaCadaDia();
        $saldos = $this->doGetSaldos();
        $rs = [];
        foreach ($saldos as $saldo) {
            $rs[] = [
                'id' => $saldo->getId(),
                'dtSaldo' => $saldo->dtSaldo->format('Y-m-d'),
                'totalRealizadas' => $saldo->totalRealizadas,
                'totalPendencias' => $saldo->totalPendencias,
            ];
        }
        return new JsonResponse($rs);
    }


    private function handleParams($dtSaldo, string $carteiraUri): void
    {
        if (!is_array($dtSaldo)) {
            $dtSaldo = 
                [
                    'after' => $dtSaldo,
                    'before' => $dtSaldo
                ];
        }
        $saldos = [];

        $this->dtIni = DateTimeUtils::parseDateStr($dtSaldo['after']);
        $this->dtFim = DateTimeUtils::parseDateStr($dtSaldo['before']);
        if (DateTimeUtils::dataMaiorQue($this->dtIni, $this->dtFim)) {
            throw new \Exception('dtIni > dtFim');
        }

        $carteiraId = EntityIdUtils::extrairIdDeUri($carteiraUri);
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

    
    private function doGetSaldos(): array
    {
        return $this->repoSaldo->findByFiltersSimpl([
            ['carteira', 'EQ', $this->carteira],
            ['dtSaldo', 'BETWEEN_DATE', [$this->dtIni, $this->dtFim], 'date']
        ], ['dtSaldo' => 'ASC'], 0, null);
    }


}
