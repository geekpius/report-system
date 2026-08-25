<?php

namespace App\Observers;

use App\Models\StudentTermResult;

class StudentTermResultObserver
{
    public function saving(StudentTermResult $studentTermResult): void
    {
        if ($studentTermResult->calculated_at === null) {
            $studentTermResult->calculated_at = now();
        }
    }
}
