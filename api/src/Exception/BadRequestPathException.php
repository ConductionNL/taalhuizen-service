<?php


namespace App\Exception;


use Throwable;

class BadRequestPathException extends \Symfony\Component\HttpFoundation\Exception\BadRequestException
{
    private string $path;
    private $value;

    public function __construct(string $message = "", string $path = "", $value = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getValue()
    {
        return $this->value;
    }

}