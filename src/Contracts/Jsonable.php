<?php

declare(strict_types=1);

namespace Nacos\Contracts;

interface Jsonable
{
    public function __toString(): string;
}
