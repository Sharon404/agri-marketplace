<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgriCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Herbs
            [
                'name' => 'Herbs',
                'children' => [
                    'Fresh Herbs', 'Dried Herbs', 'Medicinal Herbs',
                    'Culinary Herbs', 'Tea Herbs',
                ],
            ],
            // Spices
            [
                'name' => 'Spices',
                'children' => [
                    'Whole Spices', 'Ground Spices', 'Spice Blends',
                    'Chilli & Peppers', 'Seeds & Pods',
                ],
            ],
            // Cereals & Grains
            [
                'name' => 'Cereals & Grains',
                'children' => [
                    'Maize (Corn)', 'Wheat', 'Sorghum', 'Millet',
                    'Barley', 'Oats', 'Rice',
                ],
            ],
            // Legumes & Pulses
            [
                'name' => 'Legumes & Pulses',
                'children' => [
                    'Beans', 'Lentils', 'Chickpeas', 'Soya Beans',
                    'Groundnuts (Peanuts)', 'Green Grams',
                ],
            ],
            // Vegetables
            [
                'name' => 'Vegetables',
                'children' => [
                    'Leafy Greens', 'Root Vegetables', 'Bulb Vegetables',
                    'Fruiting Vegetables', 'Brassicas',
                ],
            ],
            // Fruits
            [
                'name' => 'Fruits',
                'children' => [
                    'Tropical Fruits', 'Citrus Fruits', 'Berries',
                    'Stone Fruits', 'Dried Fruits',
                ],
            ],
            // Roots & Tubers
            [
                'name' => 'Roots & Tubers',
                'children' => [
                    'Cassava', 'Sweet Potatoes', 'Irish Potatoes',
                    'Yams', 'Arrowroot',
                ],
            ],
            // Oils & Extracts
            [
                'name' => 'Oils & Extracts',
                'children' => [
                    'Cooking Oils', 'Essential Oils', 'Seed Oils',
                    'Herbal Extracts',
                ],
            ],
            // Farm Inputs
            [
                'name' => 'Farm Inputs',
                'children' => [
                    'Seeds & Seedlings', 'Organic Fertilisers',
                    'Pesticides & Herbicides', 'Soil Amendments',
                ],
            ],
            // Farm Machinery – Sale
            [
                'name' => 'Farm Machinery – Sale',
                'children' => [
                    'Tractors', 'Harvesters', 'Irrigation Equipment',
                    'Ploughs & Tillers', 'Sprayers', 'Planters',
                    'Threshers & Mills',
                ],
            ],
            // Farm Machinery – Lease / Hire
            [
                'name' => 'Farm Machinery – Lease',
                'children' => [
                    'Tractor Hire', 'Harvester Hire', 'Sprayer Hire',
                    'Transport & Logistics Hire',
                ],
            ],
            // Honey & Bee Products
            [
                'name' => 'Honey & Bee Products',
                'children' => [
                    'Raw Honey', 'Processed Honey', 'Beeswax',
                    'Propolis',
                ],
            ],
            // Livestock & Aquaculture Products
            [
                'name' => 'Livestock Products',
                'children' => [
                    'Eggs', 'Dairy', 'Hides & Skins', 'Manure & Compost',
                ],
            ],
        ];

        foreach ($categories as $cat) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                ['name' => $cat['name'], 'parent_id' => null],
            );

            foreach ($cat['children'] as $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child)],
                    ['name' => $child, 'parent_id' => $parent->id],
                );
            }
        }
    }
}
