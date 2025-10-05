<?php

namespace Database\Seeders\Tenant;

use App\Models\Tags;
use Illuminate\Database\Seeder;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Tags::truncate();
        $tags = ['有车', '有房'];
        foreach ($tags as $tag) {
            Tags::create([
                'name'     => $tag,
                'parentid' => 0,
            ]);
        }
    }
}
