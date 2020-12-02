<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;


use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegistroConferencia;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\RegistroConferenciaEntityHandler;

/**
 * Class RegistroConferenciaBusiness
 * @package CrosierSource\CrosierLibRadxBundle\Business\Financeiro
 */
class RegistroConferenciaBusiness
{

    private RegistroConferenciaEntityHandler $registroConferenciaEntityHandler;

    /**
     * RegistroConferenciaBusiness constructor.
     * @param RegistroConferenciaEntityHandler $registroConferenciaEntityHandler
     */
    public function __construct(RegistroConferenciaEntityHandler $registroConferenciaEntityHandler)
    {
        $this->registroConferenciaEntityHandler = $registroConferenciaEntityHandler;
    }


    /**
     * @param RegistroConferencia $registroConferencia
     * @throws ViewException
     */
    public function gerarProximo(RegistroConferencia $registroConferencia)
    {
        $proxMes = DateTimeUtils::incMes($registroConferencia->dtRegistro);
        $existeProximo = $this->registroConferenciaEntityHandler->getDoctrine()->getRepository(RegistroConferencia::class)->findBy(['dtRegistro' => $proxMes, 'descricao' => $registroConferencia->descricao]);
        if ($existeProximo) {
            throw new ViewException('Próximo registro já existe');
        } else {
            $novo = new RegistroConferencia();
            $novo->carteira = $registroConferencia->carteira;
            $novo->descricao = $registroConferencia->descricao;
            $novo->dtRegistro = clone $proxMes;
            $novo->valor = null;
            $this->registroConferenciaEntityHandler->save($novo);
        }
    }


}