<?php

namespace Gzhegow\Router\Core\Dispatcher\Contract;

interface RouterDispatcherRequestContractInterface
{
    public function getRequestMethod() : string;


    public function getRequestUri() : string;

    public function getRequestPath() : string;


    public function hasRequestQuery(?array &$refQuery = null) : bool;

    public function getRequestQuery() : array;


    public function hasRequestQueryString(?string &$refQueryString = null) : bool;

    public function getRequestQueryString() : string;


    public function hasRequestFragment(?string &$refFragment = null) : bool;

    public function getRequestFragment() : string;
}
