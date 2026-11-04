<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use RuntimeException;

class CatalogueThumbnails
{
    public function generate(string $media, ?int $limit = null): array
    {
        if (!in_array($media, ['movie', 'tv'], true)) throw new RuntimeException('Unknown catalogue type.');
        $table = $media === 'tv' ? 'tv_shows' : 'movies';
        $query = fn () => DB::table($table)->where(function ($query) {
            $query->whereNull('image_url_small')->orWhere('image_url_small', '');
        })->whereNotNull('image_url')->where('image_url', '<>', '');
        $rows = $query()->orderBy('id');
        if ($limit !== null) $rows->limit($limit);
        $generated = 0;
        foreach ($rows->get() as $row) {
            $contents = @file_get_contents($row->image_url, false, stream_context_create(['http' => ['timeout' => 15]]));
            if ($contents === false) throw new RuntimeException('Could not download artwork for '.$row->name.'. Retry thumbnail generation.');
            $name = $media === 'tv' ? 'tv-shows/'.$row->imdb_id.'.jpg' : basename(parse_url($row->image_url, PHP_URL_PATH) ?: $row->image_url);
            $image = Image::make($contents)->resize(229, 287);
            $thumbnail = $media === 'tv' ? $image->encode('jpg') : $image->stream();
            if (!Storage::disk('public')->put($name, (string) $thumbnail)) throw new RuntimeException('Could not save thumbnail for '.$row->name.'.');
            DB::table($table)->where('id', $row->id)->update(['image_url_small' => '/storage/'.$name, 'updated_at' => now()]);
            $generated++;
        }
        return ['generated' => $generated, 'remaining' => $query()->count()];
    }

    public function url(?string $path): ?string
    {
        if (!$path) return null;
        return Storage::disk('public')->url(preg_replace('~^/storage/~', '', $path));
    }
}
