<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = fake()->numberBetween(50, 500);
        $hasDiscount = fake()->boolean(40); // 40% chance of discount
        $discountPercentage = $hasDiscount ? fake()->numberBetween(10, 30) : 0;
        $originalPrice = $hasDiscount ? $basePrice : null;
        $finalPrice = $hasDiscount ? $basePrice * (1 - $discountPercentage / 100) : $basePrice;

        $cities = ['Barcelona', 'Madrid', 'Valencia', 'Seville', 'Malaga'];
        $city = fake()->randomElement($cities);
        
        // Barcelona coordinates: 41.3874, 2.1686
        $baseLat = 41.3874;
        $baseLng = 2.1686;
        
        $hotelTypes = ['hotel', 'room', 'entire_home'];
        $roomTypes = ['Sea View Room', 'City View Room', 'Garden View Room', 'Deluxe Suite', 'Standard Room'];
        $bedTypes = ['King Bed', 'Queen Bed', 'Twin Beds', 'Single Bed'];
        
        $amenities = fake()->randomElements([
            'Free WiFi',
            'Swimming Pool',
            'Fitness Center',
            'Restaurant',
            'Bar',
            'Parking',
            'Air Conditioning',
            '24/7 Reception',
            'Room Service',
            'Laundry Service',
            'Airport Shuttle',
            'Pet Friendly'
        ], fake()->numberBetween(3, 8));

        // Generate 3-5 image URLs
        $imageCount = fake()->numberBetween(3, 5);
        $images = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $images[] = 'https://picsum.photos/seed/' . fake()->unique()->numberBetween(1000, 9999) . '/800/600';
        }

        return [
            'name' => 'Hotel ' . fake()->company(),
            'description' => fake()->paragraphs(3, true),
            'address' => fake()->streetAddress(),
            'city' => $city,
            'country' => 'Spain',
            'latitude' => $baseLat + fake()->randomFloat(4, -0.1, 0.1),
            'longitude' => $baseLng + fake()->randomFloat(4, -0.1, 0.1),
            'price_per_night' => $finalPrice,
            'original_price' => $originalPrice,
            'discount_percentage' => $discountPercentage,
            'type' => fake()->randomElement($hotelTypes),
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'reviews_count' => fake()->numberBetween(50, 2000),
            'room_type' => fake()->randomElement($roomTypes),
            'bed_type' => fake()->randomElement($bedTypes),
            'room_size' => fake()->numberBetween(20, 80),
            'available_rooms' => fake()->numberBetween(1, 10),
            'distance_from_center' => fake()->randomFloat(2, 0.5, 10),
            'distance_from_beach' => fake()->randomFloat(2, 100, 5000),
            'has_metro_access' => fake()->boolean(70),
            'has_free_cancellation' => fake()->boolean(60),
            'has_spa_access' => fake()->boolean(40),
            'has_breakfast_included' => fake()->boolean(50),
            'is_featured' => fake()->boolean(20),
            'is_getaway_deal' => fake()->boolean(15),
            'images' => $images,
            'amenities' => $amenities,
        ];
    }
}
