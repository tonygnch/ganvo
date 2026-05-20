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
            'gallery' => [
                'name' => 'Gallery',
                'description' => 'Asymmetric editorial grid with a featured product. Built for art, photography, and handcraft brands.',
                'screenshot' => '/images/themes/gallery.svg',
            ],
            'menu' => [
                'name' => 'Menu',
                'description' => 'Restaurant-card layout with dotted leader lines from each item to its price. Built for food, drink, and tasting menus.',
                'screenshot' => '/images/themes/menu.svg',
            ],
            'tech' => [
                'name' => 'Tech',
                'description' => 'Spec-forward card grid with at-a-glance details. Built for electronics, gear, and digital products.',
                'screenshot' => '/images/themes/tech.svg',
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
