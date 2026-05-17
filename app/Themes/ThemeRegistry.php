<?php

namespace App\Themes;

class ThemeRegistry
{
    /**
     * @return array<string, array{name: string, description: string, screenshot: string}>
     */
    public static function all(): array
    {
        return [
            'default' => [
                'name' => 'Default',
                'description' => 'Clean grid catalog with bold headers. Great for visual products.',
                'screenshot' => '/images/themes/default.svg',
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'Quiet, text-forward layout. Great for boutique and editorial stores.',
                'screenshot' => '/images/themes/minimal.svg',
            ],
        ];
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::all());
    }

    public static function get(string $id): array
    {
        return self::all()[$id] ?? self::all()['default'];
    }

    public static function ids(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, string> [id => name]
     */
    public static function options(): array
    {
        return array_map(fn ($t) => $t['name'], self::all());
    }
}
