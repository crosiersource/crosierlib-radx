<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;


use CrosierSource\CrosierLibBaseBundle\Business\BaseBusiness;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegistroConferencia;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\RegistroConferenciaEntityHandler;

/**
 * Class RegistroConferenciaBusiness
 * @package CrosierSource\CrosierLibRadxBundle\Business\Financeiro
 */
class RegistroConferenciaBusiness extends BaseBusiness
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
        $proxMes = DateTimeUtils::incMes($registroConferencia->getDtRegistro());
        $existeProximo = $this->getDoctrine()->getRepository(RegistroConferencia::class)->findBy(['dtRegistro' => $proxMes, 'descricao' => $registroConferencia->getDescricao()]);
        if ($existeProximo) {
            throw new ViewException('Próximo registro já existe');
        } else {
            $novo = new RegistroConferencia();
            $novo->setCarteira($registroConferencia->getCarteira());
            $novo->setDescricao($registroConferencia->getDescricao());
            $novo->setDtRegistro($proxMes);
            $novo->setValor(null);
            $this->registroConferenciaEntityHandler->save($novo);
        }

    }


}