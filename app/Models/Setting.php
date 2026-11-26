<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['value' => 'json'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    /** A saved empty value stays empty; the default applies only when nothing was ever saved. */
    public static function get(string $key): mixed
    {
        $saved = app('store.settings');

        return $saved->has($key) ? $saved->get($key) : config("store.defaults.$key");
    }

    /** Forget what this request has read. */
    public static function flush(): void
    {
        app()->forgetInstance('store.settings');
        app()->forgetInstance('store.listed_category_ids');
    }

    /** The logo uploaded in settings, else the one shipped in public/logo.png. */
    public static function logoUrl(): ?string
    {
        if ($path = static::get('logo')) {
            return Storage::disk('public')->url($path);
        }

        return file_exists(public_path('logo.png')) ? asset('logo.png') : null;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
