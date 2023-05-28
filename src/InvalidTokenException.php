<?php
namespace FlipGive\ShopCloud;

class InvalidTokenException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Invalid token");
    }
}
