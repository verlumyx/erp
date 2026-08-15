<?php

declare(strict_types=1);

namespace App\Modules\Menu\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'permission' => $this->permission,
            'icon' => $this->icon,
        ];
    }
}
