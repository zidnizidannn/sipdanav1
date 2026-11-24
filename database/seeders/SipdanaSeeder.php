<?php

namespace Database\Seeders;

use App\Models\PriceCap;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipdanaSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $accounts = [
            ['name' => 'Checker Organisasi', 'email' => 'checker.org@sipdana.local', 'role_code' => 'CHECKER_ORG', 'entity_group' => 'CHECKER_ORG'],
            ['name' => 'Checker Yayasan', 'email' => 'checker.yayasan@sipdana.local', 'role_code' => 'CHECKER_YAYASAN', 'entity_group' => 'CHECKER_YAYASAN'],
            ['name' => 'Pemberi Anggaran', 'email' => 'approver@sipdana.local', 'role_code' => 'PEMBERI_ANGGARAN', 'entity_group' => 'APPROVER'],
            ['name' => 'KU (Keuangan)', 'email' => 'ku@sipdana.local', 'role_code' => 'KU', 'entity_group' => 'KU'],
            ['name' => 'Yayasan', 'email' => 'yayasan@sipdana.local', 'role_code' => 'YAYASAN', 'entity_group' => 'MAIN_ENTITY'],

            ['name' => 'Persinas ASAD', 'email' => 'persinas@sipdana.local', 'role_code' => 'PERSINAS', 'entity_group' => 'MAIN_ENTITY'],
            ['name' => 'LDII', 'email' => 'ldii@sipdana.local', 'role_code' => 'LDII', 'entity_group' => 'MAIN_ENTITY'],
            ['name' => 'FORSGI', 'email' => 'forsgi@sipdana.local', 'role_code' => 'FORSGI', 'entity_group' => 'MAIN_ENTITY'],
            ['name' => 'Senkom Mitra Polri', 'email' => 'senkom@sipdana.local', 'role_code' => 'SENKOM', 'entity_group' => 'MAIN_ENTITY'],
            ['name' => 'Tim Pembangunan', 'email' => 'pembangunan@sipdana.local', 'role_code' => 'TIM_PEMBANGUNAN', 'entity_group' => 'MAIN_ENTITY'],
            ['name' => 'PPG', 'email' => 'ppg@sipdana.local', 'role_code' => 'PPG', 'entity_group' => 'MAIN_ENTITY'],

            ['name' => 'RA', 'email' => 'ra@sipdana.local', 'role_code' => 'RA', 'entity_group' => 'SCHOOL'],
            ['name' => 'SDIT', 'email' => 'sdit@sipdana.local', 'role_code' => 'SDIT', 'entity_group' => 'SCHOOL'],
            ['name' => 'Wustho', 'email' => 'wustho@sipdana.local', 'role_code' => 'WUSTHO', 'entity_group' => 'SCHOOL'],
            ['name' => 'Ulya', 'email' => 'ulya@sipdana.local', 'role_code' => 'ULYA', 'entity_group' => 'SCHOOL'],
            ['name' => 'Pondok', 'email' => 'pondok@sipdana.local', 'role_code' => 'PONDOK', 'entity_group' => 'SCHOOL'],
        ];

        $firstUserId = null;

        foreach ($accounts as $idx => $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => $password,
                    'role_code' => $acc['role_code'],
                    'entity_group' => $acc['entity_group'],
                    'yayasan_mode' => 'FUND_PROVIDER',
                ]
            );
            if ($idx === 0) {
                $firstUserId = $user->id;
            }
        }

        $caps = [
            ['item_name' => 'Makan Internal', 'unit' => 'Orang', 'max_price' => 15000, 'scope' => 'ALL'],
            ['item_name' => 'Makan Tamu', 'unit' => 'Orang', 'max_price' => 25000, 'scope' => 'ALL'],
            ['item_name' => 'Snack / Coffee Break', 'unit' => 'Orang', 'max_price' => 12000, 'scope' => 'ALL'],
        ];

        foreach ($caps as $cap) {
            PriceCap::updateOrCreate(
                ['item_name' => $cap['item_name']],
                array_merge($cap, ['is_active' => true, 'created_by' => $firstUserId])
            );
        }
    }
}