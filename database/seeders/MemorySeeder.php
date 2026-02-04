<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = ['PHP', 'Laravel', 'Vue', 'React', 'Python', 'AI', 'Database', 'Docker', 'AWS', 'Linux'];
        
        for ($i = 0; $i < 50; $i++) {
            $vec = [];
            for ($j = 0; $j < 1536; $j++) {
                $vec[] = (mt_rand(0, 1000) / 1000) - 0.5;
            }
            $bin = pack('f*', ...$vec);
            
            $subject = $subjects[array_rand($subjects)];
            
            DB::table('memories')->insert([
                'agent_id' => 1,
                'content' => "This is a memory about $subject. It contains interesting facts number $i.",
                'embedding_binary' => $bin,
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now(),
            ]);
        }
    }
}
