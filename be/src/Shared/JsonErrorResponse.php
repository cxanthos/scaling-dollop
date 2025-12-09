<?php

declare(strict_types=1);

namespace App\Shared;

use Laminas\Diactoros\Response\JsonResponse;

final class JsonErrorResponse extends JsonResponse
{
    public function __construct(int $statusCode, string $reasonPhrase)
    {
        parent::__construct(
            [
                'status_code' => $statusCode,
                'reason_phrase' => $reasonPhrase,
            ],
            $statusCode,
        );
    }
}
