<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
    case Alumni = 'alumni';
    case Pending = 'pending';
}
