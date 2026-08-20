<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Teacher = 'teacher';
    case Student = 'student';
}
