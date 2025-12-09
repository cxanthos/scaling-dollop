<?php

declare(strict_types=1);

namespace App\Shared;

use JsonSerializable;

class ModelCollection implements JsonSerializable
{
    /**
     * @var array<int, JsonSerializable>
     */
    private array $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function add(JsonSerializable $model): void
    {
        $this->items[] = $model;
    }

    /**
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [];

        foreach ($this->items as $item) {
            $result[] = $item->jsonSerialize();
        }

        return $result;
    }
}
