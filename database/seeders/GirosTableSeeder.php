<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GirosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $giros = [
            [
                'acc_no' => '1270011456033',
                'acc_name' => 'Bank Mandiri USD 6033',
                'bank_id' => 1,
                'type' => 'giro',
                'curr' => 'USD',
                'project' => '001H',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('giros')->insert($giros);
    }
}

/*
11201001 - Mandiri IDR - 149.0004194751
11201002 - Mandiri IDR - 149.0002222257 (Maju)
11201003 - Mandiri IDR - 149.0007118583 (Kariangau)
11201004 - Mandiri IDR - 127.0077977997
11201028 - BPD Kaltim IDR - 154-1500096 017C
11201005 - Mandiri IDR - 127.0011024484 021C
11201006 - Mandiri IDR - 148.0021645620 022C
11201007 - Mandiri IDR - 148-0021077329 023C
11201008 - Mandiri IDR - 149-0056000005 Berau
11201014 - MTBI - Mandiri Sekuritas
11201015 - Mandiri USD - 149.0000001158 (Mapan)
11201016 - Mandiri USD - 149.0011188887
11201017 - Mandiri USD - 127.0007180878
11201018 - Mandiri USD - 127.0011456033
11201019 - Mandiri USD - 127.0050505757
11201068 - Mandiri USD - 149-00-04786929 (Escrow)
11201020 - BCA IDR - 291.099.5858
11201021 - BCA IDR - 291.779.5858
11201026 - BCA USD - 291.088.5858
11201030 - Niaga IDR - 178-0103400000
11201031 - Niaga IDR - 178-0100396001
11201035 - Niaga USD - 178-0200090000
11201036 - Niaga USD - 178-0200930008
11201040 - BNI IDR - 0585758570
11201043 - BNI USD - 0214537168
11201045 - Panin IDR - 6205007789
11201047 - Panin USD - 6206000499
11201049 - Danamon IDR - 0054482112
11201050 - Danamon IDR - 003664379249
11201052 - Danamon USD - 0054482674
11201053 - Danamon USD - 003664379504
11201055 - Nusantara Parahyangan IDR - 25100009800
11201057 - Nusantara Parahyangan USD - 25101000008
11201061 - Niaga GBP - 178.08.00001003
11201071 - BSI IDR 1807607077
*/