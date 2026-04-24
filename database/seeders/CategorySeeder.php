<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            ['name' => 'TikTok Marketing', 'description' => 'TikTok content, TikTok Shop, TikTok Ads'],
            ['name' => 'Facebook Marketing', 'description' => 'Fanpage, Group Growth, Facebook Ads'],
            ['name' => 'Instagram Marketing', 'description' => 'Reels, Stories, IG Ads'],
            ['name' => 'YouTube Growth', 'description' => 'YouTube SEO, Shorts, Monetization'],
            ['name' => 'LinkedIn B2B', 'description' => 'Personal Branding, Lead Gen, Sales Navigator'],
            ['name' => 'Content & Strategy', 'description' => 'Content planning, branding, funnel'],
        ];

        foreach ($roots as $root) {
            $slug = Str::slug($root['name']);
            $category = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $root['name'],
                    'description' => $root['description'],
                    'is_active' => true,
                ]
            );

            // A couple of children for realism
            $children = match ($slug) {
                'tiktok-marketing' => ['TikTok Shop', 'TikTok Ads', 'Livestream'],
                'facebook-marketing' => ['Facebook Ads', 'Group Growth', 'Messenger Bot'],
                'instagram-marketing' => ['Reels', 'IG Ads', 'Visual Branding'],
                'youtube-growth' => ['YouTube SEO', 'Shorts', 'Channel Strategy'],
                'linkedin-b2b' => ['Personal Branding', 'Sales Navigator', 'B2B Lead Gen'],
                'content-strategy' => ['Content Plan', 'Brand Positioning', 'Funnel Content'],
                default => [],
            };

            foreach ($children as $childName) {
                $childSlug = Str::slug($root['name'] . ' ' . $childName);
                Category::updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name' => $childName,
                        'description' => null,
                        'parent_id' => $category->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
