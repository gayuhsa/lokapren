<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Product extends BaseController
{
    public static function catalog(): array
    {
        return [
            [
                'id'          => 1,
                'name'        => 'Getuk Lindri Magelang',
                'category'    => 'food',
                'price'       => 35000,
                'rating'      => 4.8,
                'description' => 'Getuk lindri khas Magelang dari singkong pilihan, diberi taburan kelapa parut. Jajanan tradisional favorit dari Magelang.',
                'photos'      => [
                    'https://picsum.photos/seed/getuk-1/600',
                    'https://picsum.photos/seed/getuk-2/600',
                    'https://picsum.photos/seed/getuk-3/600',
                    'https://picsum.photos/seed/getuk-4/600',
                ],
            ],
            [
                'id'          => 2,
                'name'        => 'Lanting Goreng Magelang',
                'category'    => 'food',
                'price'       => 20000,
                'rating'      => 4.5,
                'description' => 'Lanting, keripik singkong khas Magelang yang dibentuk melingkar, renyah, dan gurih. Camilan ringan khas Magelang tanpa bahan pengawet.',
                'photos'      => [
                    'https://picsum.photos/seed/lanting-1/600',
                    'https://picsum.photos/seed/lanting-2/600',
                    'https://picsum.photos/seed/lanting-3/600',
                    'https://picsum.photos/seed/lanting-4/600',
                ],
            ],
            [
                'id'          => 3,
                'name'        => 'Batik Magelang',
                'category'    => 'fashion',
                'price'       => 400000,
                'rating'      => 4.9,
                'description' => 'Batik tulis khas Magelang dengan motif yang terinspirasi candi Borobudur. Dibuat secara manual oleh pengrajin batik Magelang.',
                'photos'      => [
                    'https://picsum.photos/seed/batikmlg-1/600',
                    'https://picsum.photos/seed/batikmlg-2/600',
                    'https://picsum.photos/seed/batikmlg-3/600',
                    'https://picsum.photos/seed/batikmlg-4/600',
                ],
            ],
            [
                'id'          => 4,
                'name'        => 'Kain Lurik Magelang',
                'category'    => 'fashion',
                'price'       => 175000,
                'rating'      => 4.6,
                'description' => 'Kain lurik tenun khas Magelang dengan motif garis tradisional. Ditenun menggunakan alat tenun bukan mesin oleh perajin lokal.',
                'photos'      => [
                    'https://picsum.photos/seed/lurikmlg-1/600',
                    'https://picsum.photos/seed/lurikmlg-2/600',
                    'https://picsum.photos/seed/lurikmlg-3/600',
                    'https://picsum.photos/seed/lurikmlg-4/600',
                ],
            ],
            [
                'id'          => 5,
                'name'        => 'Jamu Beras Kencur',
                'category'    => 'beauty',
                'price'       => 30000,
                'rating'      => 4.7,
                'description' => 'Jamu beras kencur tradisional yang segar dan hangat, diracik dari bahan alami pilihan. Khas Jawa Tengah dan menyegarkan untuk menjaga stamina.',
                'photos'      => [
                    'https://picsum.photos/seed/beraskencur-1/600',
                    'https://picsum.photos/seed/beraskencur-2/600',
                    'https://picsum.photos/seed/beraskencur-3/600',
                    'https://picsum.photos/seed/beraskencur-4/600',
                ],
            ],
            [
                'id'          => 6,
                'name'        => 'Minyak Telon Herbal',
                'category'    => 'beauty',
                'price'       => 30000,
                'rating'      => 4.4,
                'description' => 'Minyak telon herbal dari campuran minyak alami yang hangat. Cocok untuk menjaga tubuh tetap nyaman, khususnya untuk si kecil.',
                'photos'      => [
                    'https://picsum.photos/seed/telonmlg-1/600',
                    'https://picsum.photos/seed/telonmlg-2/600',
                    'https://picsum.photos/seed/telonmlg-3/600',
                    'https://picsum.photos/seed/telonmlg-4/600',
                ],
            ],
            [
                'id'          => 7,
                'name'        => 'Anyaman Bambu Muntilan',
                'category'    => 'home',
                'price'       => 150000,
                'rating'      => 4.3,
                'description' => 'Keranjang anyaman bambu khas Muntilan, Magelang. Dibuat manual oleh perajin bambu setempat, kokoh, dan cocok untuk berbagai kebutuhan.',
                'photos'      => [
                    'https://picsum.photos/seed/bambu-1/600',
                    'https://picsum.photos/seed/bambu-2/600',
                    'https://picsum.photos/seed/bambu-3/600',
                    'https://picsum.photos/seed/bambu-4/600',
                ],
            ],
            [
                'id'          => 8,
                'name'        => 'Miniatur Candi Batu Muntilan',
                'category'    => 'home',
                'price'       => 125000,
                'rating'      => 4.6,
                'description' => 'Miniatur candi dari batu alam khas Muntilan, Magelang. Ukiran tangan perajin batu setempat, cocok sebagai dekorasi dan suvenir.',
                'photos'      => [
                    'https://picsum.photos/seed/candi-1/600',
                    'https://picsum.photos/seed/candi-2/600',
                    'https://picsum.photos/seed/candi-3/600',
                    'https://picsum.photos/seed/candi-4/600',
                ],
            ],
        ];
    }

    public function show($id): string
    {
        $product = null;

        foreach (self::catalog() as $item) {
            if ((string) $item['id'] === (string) $id) {
                $product = $item;
                break;
            }
        }

        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('product', ['product' => $product]);
    }
}
