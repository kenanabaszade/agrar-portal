<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormatPagination
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (is_array($data) && array_key_exists('data', $data)) {
                $looksLikePaginator = isset($data['current_page'])
                    || isset($data['links'])
                    || isset($data['next_page_url'])
                    || isset($data['prev_page_url'])
                    || isset($data['last_page'])
                    || isset($data['total']);

                if ($looksLikePaginator) {
                    $metaKeys = [
                        'current_page', 'per_page', 'total', 'last_page',
                        'from', 'to', 'prev_page_url', 'next_page_url', 'path',
                    ];

                    $meta = [];
                    foreach ($metaKeys as $key) {
                        if (array_key_exists($key, $data)) {
                            $meta[$key] = $data[$key];
                        }
                    }

                    $normalized = [
                        'data' => $data['data'],
                        'meta' => $meta,
                    ];

                    $response->setData($normalized);
                }
            }
        }

        return $response;
    }
}


