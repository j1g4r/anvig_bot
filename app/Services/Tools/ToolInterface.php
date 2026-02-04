<?php

namespace App\Services\Tools;

interface ToolInterface
{
    public function name(): string;
    public function description(): string;
    public function parameters(): array;
    public function execute(array $input): string;
}
