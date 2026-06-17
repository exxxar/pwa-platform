<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = DB::table('tenants')->where("slug","test")->first();


        $stories = [];

        for ($i = 1; $i <= 10; $i++) {
            $stories[] = [
                'tenant_id'   => $tenant->id,
                'title'       => "Тестовая история #$i",
                'thumbnail'   => "https://picsum.photos/200/300?random=$i",
                'image'       => "https://picsum.photos/600/400?random=$i",
                'description' => "Описание тестовой истории #$i. Lorem ipsum dolor sit amet.",
                'config'      => json_encode([
                    'visible' => true,
                    'order' => $i,
                    'highlight' => rand(0, 1) === 1
                ]),
                'link'        => "https://example.com/story/$i",
                'link_type'   => 'external',
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ];
        }

        DB::table('stories')->insert($stories);

        $this->command->info('10 stories have been seeded successfully!');
    }
}
