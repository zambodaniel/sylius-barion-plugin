<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum\Action;

use Payum\Core\Exception\UnsupportedApiException;
use ZamboDaniel\SyliusBarionPlugin\Payum\BarionApi;

trait BarionApiTrait
{
    /**
     * @var BarionApi
     */
    private $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api): void
    {
        if (!$api instanceof BarionApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . BarionApi::class);
        }

        $this->api = $api;
    }

}