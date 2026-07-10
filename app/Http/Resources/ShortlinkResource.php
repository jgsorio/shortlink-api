<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'short_url' => config('app.url') . '/' . $this->short_url,
            'clicks' => $this->clicks,
            'is_active' => $this->is_active,
            'created_at' => Carbon::parse($this->created_at)->diffForHumans()
        ];
    }
}
