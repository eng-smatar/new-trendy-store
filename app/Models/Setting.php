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

    public static function get(string $key): mixed
    {
        $row = rescue(fn () => static::find($key), null, false);

        return $row?->value ?? config("store.defaults.$key");
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
