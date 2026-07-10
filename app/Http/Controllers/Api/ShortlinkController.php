<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShortlinkRequest;
use App\Http\Resources\ShortlinkResource;
use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShortlinkController extends Controller
{
    public function index()
    {
        $shortlinks = ShortLink::where('user_id', auth()->user()->id)->latest()->paginate();
        return ShortlinkResource::collection($shortlinks);
    }

    public function store(ShortlinkRequest $request)
    {
        $uniqueShortUrl = $this->generateShortUrl();
        $shortLink = ShortLink::create([
            'url' => $request->url,
            'short_url' => $uniqueShortUrl,
            'user_id' => auth()->user()->id,
        ]);

        return new ShortlinkResource($shortLink);
    }

    public function show(Request $request)
    {
        $shortLink = ShortLink::where('short_url', $request->short_url)->active()->first();
        if (!$shortLink) {
            return abort(404);
        }

        $shortLink->increment('clicks');
        return redirect($shortLink->url);
    }

    public function destroy(ShortLink $shortLink)
    {
        $shortLink->is_active = false;
        $shortLink->save();
        return response()->noContent();
    }

    private function generateShortUrl(): string
    {
        $uniqueShortUrl = Str::random(8);
        $shortUrlExists = ShortLink::where('short_url', $uniqueShortUrl)->exists();

        return $shortUrlExists ? $this->generateShortUrl() : $uniqueShortUrl;
    }
}
