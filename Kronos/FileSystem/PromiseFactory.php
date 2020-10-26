<?php

namespace Kronos\FileSystem;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;

class PromiseFactory
{
    public function createPromise(): Promise
    {
        return new Promise();
    }

    public function createFulfilledPromise($value): FulfilledPromise
    {
        return new FulfilledPromise($value);
    }

    public function createRejectedPromise($reason): RejectedPromise
    {
        return new RejectedPromise($reason);
    }
}
