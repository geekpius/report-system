<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Promoted = 'promoted';
    case Transferred = 'transferred';
    case Repeated = 'repeated';
}
