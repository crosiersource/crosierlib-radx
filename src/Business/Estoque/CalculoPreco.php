<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Estoque;


use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\DepreciacaoPreco;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\DepreciacaoPrecoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Lógicas para cálculo de preços.
 *
 * @author Carlos Eduardo Pauluk
 */
class CalculoPreco
{

    private EntityManagerInterface $doctrine;

    /**
     * CalculoPreco constructor.
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    /**
     * Cálculo:
     * precoPrazo = precoCusto * custoFinanceiroCompl * coeficiente
     * custoFinanceiroCompl = 1.0 / (1.0 - 15%)
     * precoPrazo = precoCusto * (1 / (1 - 15%))
     * precoVista = precoPrazo * (1 - 10%)
     *
     * @param array $preco
     */
    public function calcularPreco(array &$preco): void
    {
        if (!($preco['margem'] ?? false)) {
            $this->calcularMargem($preco);
        }
        if (!($preco['precoCusto'] ?? false)) {
            throw new \LogicException('Preço de custo nulo.');
        }

        $this->calcularCoeficiente($preco);

        if (!($preco['coeficiente'] ?? false)) {
            throw new \LogicException('É necessário o coeficiente para calcular os preços');
        }

        $precoCusto = $preco['precoCusto'];
        $coeficiente = $preco['coeficiente'];
        $custoFinanceiro = $preco['custoFinanceiro'];
        $custoFinanceiroCompl = 1.0 - (float)$custoFinanceiro;

        $coeficiente_x_precoCusto = bcmul($coeficiente, $precoCusto, 8);
        $coeficiente_x_precoCusto_d_ = bcdiv($coeficiente_x_precoCusto,$custoFinanceiroCompl,8);
        $precoPrazo = $coeficiente_x_precoCusto_d_arr = DecimalUtils::round($coeficiente_x_precoCusto_d_, 8, DecimalUtils::ROUND_HALF_UP);

        $precoPrazo_centavosComDezenaExata = DecimalUtils::round($precoPrazo, 1, DecimalUtils::ROUND_HALF_UP);

        $descontoAVista = 1.00 - (float)$preco['custoFinanceiro'];

        $precoVista = bcmul($precoPrazo_centavosComDezenaExata, $descontoAVista, 2);

        $preco['precoPrazo'] = $precoPrazo_centavosComDezenaExata;
        $preco['precoVista'] = $precoVista;
    }


    /**
     * coeficiente = ( 1.0 / (1.0 - custoOperacional + margem) ) * depreciacaoPrazo
     * @param array $preco
     */
    public function calcularCoeficiente(array &$preco): void
    {
        $prazo = (int)$preco['prazo'];
        // obtém o depreciacaoPrazo da base de dados
        if ($prazo === null || $prazo === '' || $prazo < 0) {
            throw new \LogicException('Prazo deve ser um número inteiro igual ou maior que 0.');
        }

        $margem = (float)$preco['margem'];
        if ($margem > 0.9999) {
            throw new \LogicException('Margem superior a 99,99%');
        }

        $custoOperacional = (float)$preco['custoOperacional']; // (float)bcdiv($preco['custoOperacional'], '100.0', 3);
        if ($custoOperacional < 0 || $custoOperacional > 0.99) {
            throw new \LogicException('Custo Operacional deve estar entre 0 e 0.99');
        }

        $depreciacaoPrazo = $this->doctrine->getRepository(DepreciacaoPreco::class)->findDepreciacaoByPrazo($prazo);

        // Antes a margem não era passada em número decimal
        $margemPorcent = $margem; // (float)bcdiv($margem, '100.00', 4);


        $margemMaximaPorcent = 1.0 - $custoOperacional - 0.0001;
        if ($margemPorcent > $margemMaximaPorcent) {
            throw new \LogicException('Margem não pode ser superior a ' . $margemMaximaPorcent . ' (C.O.: ' . $custoOperacional . ')');
        }

        $coefNaoDeflacionado = (float)bcsub('1.0', ($custoOperacional + $margemPorcent), 25);
        $coefNaoDeflacionadoInv = $coefNaoDeflacionado > 0 ? bcdiv('1.0', $coefNaoDeflacionado, 25) : 0;

        $coeficiente = DecimalUtils::round((float)bcmul($coefNaoDeflacionadoInv, $depreciacaoPrazo, 25), 3);
        // retorno
        $preco['coeficiente'] = $coeficiente;
    }

    /**
     * Calcula a margem a partir de um preço a prazo já dado.
     * Fórmula: margem = 1 - custoOperacional - (precoCusto * depreciacaoPrazo / precoPrazo * (1 - custoFinanceiro)).
     *
     * @param array $preco
     */
    public function calcularMargem(array &$preco): void
    {
        $preco['custoOperacional'] = (float)$preco['custoOperacional'];
        if ($preco['custoOperacional'] < 0 || $preco['custoOperacional'] > 0.99) {
            throw new \LogicException('Custo Operacional deve estar entre 0 e 0.99');
        }
        /** @var DepreciacaoPrecoRepository $repoDepreciacaoPreco */
        $repoDepreciacaoPreco = $this->doctrine->getRepository(DepreciacaoPreco::class);
        $depreciacaoPrazo = $repoDepreciacaoPreco->findDepreciacaoByPrazo($preco['prazo']);

        $precoCusto = (float)$preco['precoCusto'];
        if (!($preco['precoPrazo'] ?? false)) {
            throw new \LogicException('Impossível calcular margem sem preço prazo');
        }
        $precoPrazo = (float)$preco['precoPrazo'];
        $custoOperacionalCompl = (float)bcsub(1.0, $preco['custoOperacional'], 2);
        $custoFinanceiroCompl = (float)bcsub(1.0, $preco['custoFinanceiro'], 2);

        $a = bcmul($precoCusto, $depreciacaoPrazo, 12);
        $b = bcmul($precoPrazo, $custoFinanceiroCompl, 12);
        $div = bcdiv($a, $b, 12);

        $margem = bcsub($custoOperacionalCompl, $div, 4);
        $margem = bcmul($margem, '100.0', 2);
        $preco['margem'] = bcdiv(round($margem, 2), 100.0, 5);

    }


}
