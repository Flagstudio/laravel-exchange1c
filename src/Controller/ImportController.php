<?php
/**
 * This file is part of bigperson/laravel-exchange1c package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Bigperson\LaravelExchange1C\Controller;

use Bigperson\Exchange1C\Exceptions\Exchange1CException;
use Bigperson\Exchange1C\Services\CatalogService;
use Bigperson\LaravelExchange1C\OrderExchangeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class ImportController.
 */
class ImportController extends Controller
{
    /**
     * @param Request        $request
     * @param CatalogService $service
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function request(Request $request, CatalogService $service, OrderExchangeService $orderExchangeService)
    {
        $mode = $request->get('mode');
        $type = $request->get('type');

        try {
            switch ($type) {
                case 'catalog':
                    if (!method_exists($service, $mode)) {
                        throw new Exchange1CException("Exchange catalog mode={$mode} not foreseen by exchange protocol.");
                    }
                    $response = $service->$mode();
                    \Log::debug("exchange_1c: response=" . "\n" . $response);

                    return response($response, 200, ['Content-Type', 'text/plain']);

                case 'sale':
                    if (!method_exists($orderExchangeService, $mode)) {
                        throw new Exchange1CException("Exchange orders mode={$mode} not foreseen by exchange protocol.");
                    }

                    if ($mode == 'success') {
                        if ($orderExchangeService->$mode()) {
                            \Log::error("exchange_1c: orders NOT marked as sent. ERROR");
                        }
                        else {
                            \Log::debug("exchange_1c: orders marked as sent");
                        }
                        break;
                    }

                    if ($mode == 'query') {
                        $fileResponse = $orderExchangeService->$mode();
                        \Log::debug("exchange_1c: file with orders sent");
                        return $fileResponse;
                    }

                    $response = $orderExchangeService->$mode();
                    \Log::debug("exchange_1c: response=" . "\n" . $response);

                    return response($response, 200, ['Content-Type', 'text/plain']);

                default:
                    throw new Exchange1CException("Exchange type={$type} not foreseen by exchange protocol.");

            }
        }
        catch (Exchange1CException $e) {
            \Log::error("Exchange 1C: FAIL\n".$e->getMessage()."\n".$e->getFile()."\n".$e->getLine()."\n");

            $response = "failure\n";
            $response .= $e->getMessage()."\n";
            $response .= $e->getFile()."\n";
            $response .= $e->getLine()."\n";

            return response($response, 500, ['Content-Type', 'text/plain']);
        }
    }
}
