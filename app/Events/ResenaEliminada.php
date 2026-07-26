<?php

namespace App\Events;

use App\Models\Taller;

class ResenaEliminada
{
    public function __construct(
        public readonly Taller $taller,
    ) {}
}
