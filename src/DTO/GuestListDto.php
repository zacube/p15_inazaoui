<?php

namespace App\DTO;

class GuestListDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $mediaCount,
    ) {}
}