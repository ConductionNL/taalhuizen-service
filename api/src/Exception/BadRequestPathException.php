<?php

namespace App\Exception;

use Throwable;

class BadRequestPathException extends \Symfony\Component\HttpFoundation\Exception\BadRequestException
{
    private string $path;
    private array $data;

    public function __construct(string $message = '', string $path = '', array $data = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->path = $path;
        $this->data = $data;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
