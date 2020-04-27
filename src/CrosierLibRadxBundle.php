<?php

namespace CrosierSource\CrosierLibRadxBundle;


use CrosierSource\CrosierLibRadxBundle\DependencyInjection\CrosierLibRadxExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class CrosierLibRadxBundle
 *
 * @package CrosierSource\CrosierLibRadxBundle
 * @author Carlos Eduardo Pauluk
 */
class CrosierLibRadxBundle extends Bundle
{


    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new CrosierLibRadxExtension();
        }
        return $this->extension;

    }


}