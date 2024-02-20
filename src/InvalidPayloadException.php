<?php
namespace FlipGive\Rewards;

class InvalidPayloadException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Invalid payload");
    }
}
