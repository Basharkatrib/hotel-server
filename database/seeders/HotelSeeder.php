<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hotelsData = [
            [
                'name' => 'Royal Spain Palace',
                'country' => 'Spain',
                'city' => 'Madrid',
                'lat' => 40.4168,
                'lng' => -3.7038,
                'images' => [
                    'https://images.unsplash.com/photo-1678913355695-2f6c08243ce5?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1723465308831-29da05e011f3?q=80&w=1332&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1672055290450-0fbc026c5b21?q=80&w=686&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1771515340397-86a96fe265ca?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1604397852861-2c1555f08852?q=80&w=880&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://plus.unsplash.com/premium_photo-1675616563084-63d1f129623d?q=80&w=1169&auto=format&fit=crop', 'https://images.unsplash.com/photo-1662841540530-2f04bb3291e8?q=80&w=1076&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631048649038-e31d38df5a25?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1661962340349-6ea59fff7e7b?q=80&w=1074&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1661877303180-19a028c21048?q=80&w=1074&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049035260-64d09b5d6912?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1663061414669-bb34bcd3ff2f?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049035257-02039c597992?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049035433-4409c68ca6cf?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Damascus Heritage Inn',
                'country' => 'Syria',
                'city' => 'Damascus',
                'lat' => 33.5138,
                'lng' => 36.2765,
                'images' => [
                    'https://images.unsplash.com/photo-1678913308053-316cee77afe9?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1756245994834-61974c290b61?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1678913307977-aef05ef2df60?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1771293549382-62829fad8f2d?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1680596830414-e385491c7b07?q=80&w=1332&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1742226789249-32cfaac0ff5e?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049307729-d5db33d15ed1?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049422186-4b0569fed517?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1631049552351-16da4767cc98?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049035509-076f10beb2fe?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1629140727571-9b5c6f6267b4?q=80&w=627&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1663126634394-427767eca961?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1594130139005-3f0c0f0e7c5e?q=80&w=1112&auto=format&fit=crop', 'https://images.unsplash.com/photo-1630660664869-c9d3cc676880?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Berlin Grand Plaza',
                'country' => 'Germany',
                'city' => 'Berlin',
                'lat' => 52.5200,
                'lng' => 13.4050,
                'images' => [
                    'https://images.unsplash.com/photo-1678913355695-2f6c08243ce5?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1723465308831-29da05e011f3?q=80&w=1332&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1672055290450-0fbc026c5b21?q=80&w=686&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1771515340397-86a96fe265ca?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1604397852861-2c1555f08852?q=80&w=880&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1631048648924-e8723adbf571?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049307567-d20d6d97f64a?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1678297270015-226732d6aae4?q=80&w=687&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1631049307421-2ee48a375aca?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1663076153319-e65d2ec746cf?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631048835049-b0b7ee4be40b?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1631049421631-051b1511cef9?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1663126953248-3b3bd25402e2?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1631049035293-745416a778d0?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Seville Sunshine Resort',
                'country' => 'Spain',
                'city' => 'Seville',
                'lat' => 37.3891,
                'lng' => -5.9845,
                'images' => [
                    'https://images.unsplash.com/photo-1655853116923-eb7758c41522?q=80&w=688&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1649489357325-144ae0b72788?q=80&w=1855&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1642550918683-0196bda8be7f?q=80&w=1074&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1625582421421-0d27eafe68fb?q=80&w=1074&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1743019486333-e9f7032b912e?q=80&w=1170&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1631048835184-3f0ceda91b75?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1724365919414-c773579405f4?q=80&w=687&auto=format&fit=crop', 'https://images.unsplash.com/photo-1663756915301-2ba688e078cf?q=80&w=1243&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1714645877530-c3df9d2878dc?q=80&w=1169&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1724701623797-3a5aedfdd00f?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1673687778498-5ddd20749408?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1724659215886-3674d0a05845?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1578898887932-dce23a595ad4?q=80&w=687&auto=format&fit=crop', 'https://images.unsplash.com/photo-1673687778498-5ddd20749408?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Aleppo Ancient Stay',
                'country' => 'Syria',
                'city' => 'Aleppo',
                'lat' => 36.2021,
                'lng' => 37.1343,
                'images' => [
                    'https://images.unsplash.com/photo-1720346901949-dad7636933bf?q=80&w=628&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1726609817287-585eb044874a?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1694026066117-28a458651383?q=80&w=1172&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1748640650663-39365c5058ec?q=80&w=1137&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1605789347875-f1ffb487365d?q=80&w=735&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1642596815362-4f4d0e7afbd5?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1741282698801-39e8ed789a33?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1724365919261-9aaae9e816f1?q=80&w=687&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1718894071528-1108a094cc78?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1728488443956-528b79ef38a2?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1676319481400-2c1f458f8730?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1631049035115-f96132761a38?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1679588115733-dbec5e2f09eb?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1661258279966-cfeb51c98327?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Munich Modern Hotel',
                'country' => 'Germany',
                'city' => 'Munich',
                'lat' => 48.1351,
                'lng' => 11.5820,
                'images' => [
                    'https://images.unsplash.com/photo-1721539151779-e6dc7f9de376?q=80&w=1074&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1603259126022-accb4f78c03d?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1629996628328-a35ab39c5e14?q=80&w=1074&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1644591648977-fec6e50a9c77?q=80&w=735&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1773563946246-d12cccc64eca?q=80&w=1170&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://plus.unsplash.com/premium_photo-1682094031102-45e46cbbbf90?q=80&w=687&auto=format&fit=crop', 'https://images.unsplash.com/photo-1721742120278-177a7d5e934c?q=80&w=1376&auto=format&fit=crop', 'https://images.unsplash.com/photo-1718527573417-64cdfbbaec30?q=80&w=1174&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1629140727571-9b5c6f6267b4?q=80&w=627&auto=format&fit=crop', 'https://images.unsplash.com/photo-1718527573417-64cdfbbaec30?q=80&w=1174&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1733514691550-602d66909f26?q=80&w=1171&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1678402541226-73e17cd3cfd1?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1630568238435-27b47667969b?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Barcelona Beach Suites',
                'country' => 'Spain',
                'city' => 'Barcelona',
                'lat' => 41.3851,
                'lng' => 2.1734,
                'images' => [
                    'https://images.unsplash.com/photo-1759849543080-71ea048ab9ca?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1733253870419-81b7b80d52cf?q=80&w=735&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1732038330792-87e6dfeded6f?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1602103088261-9e937c58e72e?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1707699263780-fab8e540953e?q=80&w=764&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1554861148-982401c111fa?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1712172424737-fb0e5bb99e18?q=80&w=1074&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1682094026083-fc4329ef6771?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1721044169036-ab78adc84bf4?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1702675301342-cac2dc3ef15a?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1678402545080-2353b489c0c3?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1737517302831-e7b8a8eaa97c?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1744000311897-510b64f9a2e2?q=80&w=687&auto=format&fit=crop', 'https://images.unsplash.com/photo-1649009566608-35c0395db3be?q=80&w=1074&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Homs Horizon Hotel',
                'country' => 'Syria',
                'city' => 'Homs',
                'lat' => 34.7324,
                'lng' => 36.7137,
                'images' => [
                    'https://images.unsplash.com/photo-1739138699221-766e690b229f?q=80&w=692&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1709335658123-cf5c5b9b52fb?q=80&w=762&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1772553320097-615ad72f9ca8?q=80&w=1332&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1772903191730-fa9bc478c1de?q=80&w=1170&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1721539403072-37138203d08c?q=80&w=720&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://plus.unsplash.com/premium_photo-1686782502543-96f699c3889c?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1572987669554-0ba2ba9aee1f?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1727872915210-a1dc73f8a85d?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1746549854913-3be88c9e4352?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1666960273090-1c2c77a48aae?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1755311905567-b7068d2cbacd?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1728488443364-72164f2a9e3a?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1673687784076-f669a5cf98c0?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1755311905567-b7068d2cbacd?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Hamburg Harbor Inn',
                'country' => 'Germany',
                'city' => 'Hamburg',
                'lat' => 53.5511,
                'lng' => 9.9937,
                'images' => [
                    'https://images.unsplash.com/photo-1765439178218-e54dcbb64bcb?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1721549900543-564fe2df079a?q=80&w=736&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1771775528767-503f1bb5df2d?q=80&w=1169&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1766164416048-ccc611d5b124?q=80&w=687&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1762742316793-b1845065429a?q=80&w=736&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://images.unsplash.com/photo-1744187170993-6591089e2674?q=80&w=1172&auto=format&fit=crop', 'https://images.unsplash.com/photo-1560184897-502a475f7a0d?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1673014201629-287913904dbe?q=80&w=1211&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1572297258415-b9cc3cc5bb71?q=80&w=1171&auto=format&fit=crop', 'https://images.unsplash.com/photo-1660150912348-9c1b47581e7b?q=80&w=1170&auto=format&fit=crop', 'https://plus.unsplash.com/premium_photo-1774048162005-c1e17b0cf502?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://plus.unsplash.com/premium_photo-1774048162005-c1e17b0cf502?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1671037198031-5d8b12d3d2b1?q=80&w=1331&auto=format&fit=crop', 'https://images.unsplash.com/photo-1691588857108-57968e0778c0?q=80&w=1169&auto=format&fit=crop']],
                ]
            ],
            [
                'name' => 'Frankfurt Financial Stay',
                'country' => 'Germany',
                'city' => 'Frankfurt',
                'lat' => 50.1109,
                'lng' => 8.6821,
                'images' => [
                    'https://images.unsplash.com/photo-1772816797539-99d876ad5f25?q=80&w=735&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1683290845356-920a3307adf4?q=80&w=1161&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1670236815012-ba57a69d0b41?q=80&w=687&auto=format&fit=crop',
                    'https://plus.unsplash.com/premium_photo-1732025157740-c631ee2a0cbb?q=80&w=1159&auto=format&fit=crop',
                    'https://images.unsplash.com/photo-1766393195967-bb27203ba333?q=80&w=1170&auto=format&fit=crop',
                ],
                'rooms' => [
                    ['images' => ['https://plus.unsplash.com/premium_photo-1683124695653-6146113a51ea?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1722942626231-3f28df853721?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1721739514140-5d7d98003ecd?q=80&w=1170&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1721739514140-5d7d98003ecd?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1635108197086-5107e1e98107?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1759691321555-94fed84288fa?q=80&w=1171&auto=format&fit=crop']],
                    ['images' => ['https://images.unsplash.com/photo-1762117360817-95a0c71a405e?q=80&w=1332&auto=format&fit=crop', 'https://images.unsplash.com/photo-1743410976099-6114097db9ba?q=80&w=1170&auto=format&fit=crop', 'https://images.unsplash.com/photo-1533633310920-cc9bf1e7f9b0?q=80&w=1170&auto=format&fit=crop']],
                ]
            ],
        ];

        foreach ($hotelsData as $index => $data) {
            $hotel = Hotel::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . rand(1000, 9999),
                'description' => 'Experience luxury and comfort at ' . $data['name'] . '. Located in the heart of ' . $data['city'] . ', ' . $data['country'] . ', we offer world-class amenities and exceptional service to make your stay unforgettable.',
                'address' => rand(1, 999) . ' Global Ave, ' . $data['city'],
                'city' => $data['city'],
                'country' => $data['country'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'price_per_night' => rand(150, 500),
                'type' => 'hotel',
                'rating' => rand(35, 50) / 10,
                'reviews_count' => rand(100, 2000),
                'images' => $data['images'],
                'amenities' => ['Free WiFi', 'Swimming Pool', 'Air Conditioning', '24/7 Reception', 'Restaurant'],
            ]);

            foreach ($data['rooms'] as $roomIndex => $roomData) {
                Room::create([
                    'hotel_id' => $hotel->id,
                    'name' => 'Luxurious ' . ($roomIndex === 0 ? 'Single' : ($roomIndex === 1 ? 'Double' : 'Suite')) . ' Room',
                    'description' => 'A spacious and well-appointed room featuring modern decor and premium bedding.',
                    'type' => $roomIndex === 0 ? 'single' : ($roomIndex === 1 ? 'double' : 'suite'),
                    'size' => rand(30, 70),
                    'max_guests' => $roomIndex + 1,
                    'price_per_night' => $hotel->price_per_night + rand(20, 100),
                    'images' => $roomData['images'],
                    'is_available' => true,
                ]);
            }
        }

        $this->command->info('10 hotels with 3 rooms each created successfully with specified images!');
    }
}
