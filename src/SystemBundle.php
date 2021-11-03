<?php

namespace Kmi\SystemBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SystemBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}