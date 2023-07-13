<?php

namespace ArchiElite\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LevelCountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'level' => $this->level->value,
            'level_name' => $this->level->getName(),
            'level_class' => $this->level->getClass(),
            'count' => $this->count,
            'selected' => $this->selected,
        ];
    }
}
