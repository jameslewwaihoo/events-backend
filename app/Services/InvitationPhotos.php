<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAsset;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class InvitationPhotos
{
    public const PLACEMENTS = [
        'cover' => 'Cover photo',
        'rom' => 'ROM gallery',
        'story' => 'Story photo sphere',
        'portrait' => 'Story portrait for reduced motion',
    ];
    public const SINGLE_PHOTO_PLACEMENTS = ['cover', 'portrait'];

    public static function supports(Event $event): bool
    {
        return $event->usesWeddingInvitation();
    }

    public static function query(Event $event): HasMany
    {
        return $event->assets()->whereIn('type', array_map(
            fn ($placement) => self::type($placement),
            array_keys(self::PLACEMENTS)
        ));
    }

    public static function type(string $placement): string
    {
        return 'invitation-'.$placement;
    }

    public static function catalog(Event $event): array
    {
        $groups = self::query($event)->orderBy('sort_order')->orderBy('id')->get()->groupBy('type');
        $catalog = [];

        foreach (self::PLACEMENTS as $placement => $label) {
            $photos = $groups->get(self::type($placement), collect())->map(function (EventAsset $photo) use ($event) {
                $params = [$event->slug, pathinfo($photo->path, PATHINFO_FILENAME)];
                return [
                    'id' => $photo->id,
                    'src' => route('public.events.photos.image', [...$params, 'full'], false),
                    'thumbnail' => route('public.events.photos.image', [...$params, 'thumb'], false),
                    'width' => $photo->meta['width'],
                    'height' => $photo->meta['height'],
                    'alt' => $photo->title,
                    'altZh' => $photo->meta['alt_zh'] ?? null,
                ];
            })->values();

            $catalog[$placement] = in_array($placement, self::SINGLE_PHOTO_PLACEMENTS, true)
                ? $photos->first()
                : $photos->all();
        }

        return $catalog;
    }

    public static function path(Event $event, string $version): string
    {
        return 'invitation-photos/'.$event->id.'/'.$version.'.jpg';
    }

    public static function image(Request $request, EventAsset $photo, string $variant, bool $public): BinaryFileResponse
    {
        $path = $variant === 'thumb' ? self::thumbnailPath($photo->path) : $photo->path;
        $file = Storage::disk('local')->path($path);
        abort_unless(is_file($file), 404);

        $response = response()->file($file, ['Content-Type' => 'image/jpeg']);
        $response->headers->set('Cache-Control', $public ? 'public, no-cache' : 'private, no-store');
        if ($public) {
            $response->setEtag(pathinfo($path, PATHINFO_FILENAME));
            $response->isNotModified($request);
        }

        return $response;
    }

    public static function store(Event $event, string $source, ?string $altZh = null): array
    {
        $size = @getimagesize($source);
        if (!$size || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || max($size[0], $size[1]) > 8192 || $size[0] * $size[1] > 24000000) {
            throw ValidationException::withMessages(['photo' => 'Choose a JPG, PNG or WebP photo up to 24 megapixels and 8,192 pixels per side.']);
        }

        $image = match ($size[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
        };

        if (!$image) {
            throw ValidationException::withMessages(['photo' => 'The photo could not be decoded. Choose a different file.']);
        }

        if ($size[2] === IMAGETYPE_JPEG) {
            $orientation = (int) (@exif_read_data($source, 'IFD0')['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            }
            $degrees = match ($orientation) {
                3 => 180,
                5, 8 => 90,
                6, 7 => -90,
                default => 0,
            };
            if ($degrees) {
                $image = imagerotate($image, $degrees, 0);
                if (!$image) {
                    throw new RuntimeException('Unable to orient the invitation photo.');
                }
            }
        }

        $path = self::path($event, (string) Str::uuid());
        $disk = Storage::disk('local');
        if (!$disk->makeDirectory(dirname($path))) {
            throw new RuntimeException('Unable to create the invitation photo directory.');
        }

        try {
            [$width, $height] = self::writeJpeg($image, $disk->path($path), 1600, 86);
            self::writeJpeg($image, $disk->path(self::thumbnailPath($path)), 384, 82);
        } catch (Throwable $exception) {
            self::delete($path);
            throw $exception;
        }

        return ['path' => $path, 'meta' => [
            'width' => $width, 'height' => $height, 'alt_zh' => $altZh,
        ]];
    }

    public static function delete(string $path): void
    {
        Storage::disk('local')->delete([$path, self::thumbnailPath($path)]);
    }

    private static function thumbnailPath(string $path): string
    {
        return substr($path, 0, -4).'-thumb.jpg';
    }

    private static function writeJpeg(\GdImage $image, string $destination, int $limit, int $quality): array
    {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $scale = min(1, $limit / max($originalWidth, $originalHeight));
        $width = max(1, (int) round($originalWidth * $scale));
        $height = max(1, (int) round($originalHeight * $scale));
        $resized = imagecreatetruecolor($width, $height);

        if (!$resized) {
            throw new RuntimeException('Unable to resize the invitation photo.');
        }
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        if (!imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight)
            || !imagejpeg($resized, $destination, $quality)) {
            throw new RuntimeException('Unable to save the invitation photo.');
        }

        clearstatcache(true, $destination);
        if (!is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('Unable to save the invitation photo.');
        }

        return [$width, $height];
    }
}
