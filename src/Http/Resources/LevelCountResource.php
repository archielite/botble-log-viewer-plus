<?php

namespace ArchiElite\LogViewer\Http\Resources;

use ArchiElite\LogViewer\LogLevels\LevelInterface;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read LevelInterface $level
 * @property-read int $count
 * @property-read bool $selected
 */
class LevelCountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'level' => $this->level->value,
            'level_name' => $this->level->getName(),
            'level_class' => $this->level->getClass()->value,
            'count' => $this->count,
            'selected' => $this->selected,
        ];
    }
}
