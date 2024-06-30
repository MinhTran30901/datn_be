<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('vi_VN'); // Sử dụng Faker với ngôn ngữ tiếng Việt

        $users = [];

        for ($i = 0; $i < 50; $i++) {
            $latitude = $faker->latitude(20.8, 21.3); // Tọa độ xung quanh Hà Nội
            $longitude = $faker->longitude(105.75, 106.05);

            $users[] = [
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'username' => $faker->userName,
                'birthday' => $faker->date('Y-m-d', '-18 years'),
                'age' => $faker->numberBetween(18, 60),
                'image_url' => $faker->imageUrl(),
                'description' => $faker->sentence,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'height' => $faker->numberBetween(150, 200),
                'smoking' => $faker->numberBetween(0, 2), // Giá trị của smoking là 0, 1 hoặc 2
                'alcohol' => $faker->numberBetween(0, 2), // Giá trị của alcohol là 0, 1 hoặc 2
                'address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insert($users);
    }
}
