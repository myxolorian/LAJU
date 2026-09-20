<?php

namespace App\Enums;

enum ClubRole: string
{
    case Admin = 'admin';
    case Pelatih = 'pelatih';
    case Anggota = 'anggota';
}
