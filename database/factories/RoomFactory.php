<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roomTypes = [
            'single' => [
                'name' => 'Single Room',
                'single_beds' => 1,
                'double_beds' => 0,
                'max_guests' => 1,
                'base_price' => fake()->numberBetween(50, 100),
            ],
            'double' => [
                'name' => 'Double Room',
                'single_beds' => 0,
                'double_beds' => 1,
                'max_guests' => 2,
                'base_price' => fake()->numberBetween(80, 150),
            ],
            'suite' => [
                'name' => 'Suite',
                'single_beds' => 0,
                'double_beds' => 1,
                'king_beds' => 1,
                'max_guests' => 4,
                'base_price' => fake()->numberBetween(200, 400),
            ],
            'deluxe' => [
                'name' => 'Deluxe Room',
                'single_beds' => 0,
                'double_beds' => 0,
                'king_beds' => 1,
                'max_guests' => 2,
                'base_price' => fake()->numberBetween(150, 250),
            ],
        ];

        $type = fake()->randomElement(['single', 'double', 'suite', 'deluxe']);
        $roomConfig = $roomTypes[$type];
        
        $views = ['Sea View', 'City View', 'Mountain View', 'Garden View', 'Pool View'];
        $roomName = $roomConfig['name'];
        if (fake()->boolean(60)) {
            $roomName = fake()->randomElement($views) . ' ' . $roomName;
        }

        $basePrice = $roomConfig['base_price'];
        $hasDiscount = fake()->boolean(30);
        $discountPercentage = $hasDiscount ? fake()->numberBetween(5, 25) : 0;
        $originalPrice = $hasDiscount ? $basePrice : null;
        $finalPrice = $hasDiscount ? $basePrice * (1 - $discountPercentage / 100) : $basePrice;

        // Generate 2-4 room images
        $imageCount = fake()->numberBetween(2, 4);
        $images = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $images[] = 'https://picsum.photos/seed/' . fake()->numberBetween(1000, 9999) . '/800/600';
        }

        return [
            'hotel_id' => Hotel::inRandomOrder()->first()?->id ?? Hotel::factory(),
            'name' => $roomName,
            'description' => fake()->paragraph(3),
            'type' => $type,
            'size' => fake()->numberBetween(20, 80),
            'max_guests' => $roomConfig['max_guests'],
            'single_beds' => $roomConfig['single_beds'] ?? 0,
            'double_beds' => $roomConfig['double_beds'] ?? 0,
            'king_beds' => $roomConfig['king_beds'] ?? 0,
            'queen_beds' => 0,
            'price_per_night' => round($finalPrice, 2),
            'original_price' => $originalPrice ? round($originalPrice, 2) : null,
            'discount_percentage' => $discountPercentage,
            'is_available' => fake()->boolean(90),
            'has_breakfast' => fake()->boolean(70),
            'has_wifi' => fake()->boolean(95),
            'has_ac' => fake()->boolean(90),
            'has_tv' => fake()->boolean(85),
            'has_minibar' => fake()->boolean(60),
            'has_safe' => fake()->boolean(70),
            'has_balcony' => fake()->boolean(40),
            'has_bathtub' => fake()->boolean(50),
            'has_shower' => fake()->boolean(95),
            'no_smoking' => fake()->boolean(80),
            'view' => fake()->randomElement(['city', 'sea', 'mountain', 'garden', 'pool', 'none']),
            'images' => $images,
            'rating' => fake()->randomFloat(1, 3.0, 5.0),
            'reviews_count' => fake()->numberBetween(10, 500),
            'is_active' => fake()->boolean(95),
            'is_featured' => fake()->boolean(20),
        ];
    }
}
