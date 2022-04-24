<?php

declare(strict_types=1);

namespace Pchapl\CodePrint;

use Pchapl\CodeGen\EntityInterface;

interface PrettyPrinterInterface
{
    public function print(EntityInterface $entity): string;
}
