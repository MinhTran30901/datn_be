<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Interest;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $interests = [
            'Đọc sách',
            'Du lịch',
            'Nghe nhạc',
            'Chơi thể thao',
            'Xem phim',
            'Chụp ảnh',
            'Nấu ăn',
            'Thời trang',
            'Công nghệ',
            'Thiên nhiên',
            'Chăm sóc thú cưng',
            'Hoạt động xã hội',
            'Yoga',
            'Thiền',
            'Vẽ tranh',
            'Làm vườn',
            'Đánh cờ',
            'Học ngôn ngữ mới',
            'Sưu tầm',
            'Tập gym'
        ];

        foreach ($interests as $interest) {
            Interest::create(['name' => $interest]);
        }
    }
}
