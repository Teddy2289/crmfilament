<?php

namespace Tests\Unit;

use App\Models\Prospect;
use PHPUnit\Framework\Attributes\Test;

class DepartmentLabelTest
{
    #[Test]
    public function it_formats_the_department_code_with_its_name(): void
    {
        $prospect = new Prospect(['departement' => '75']);

        $this->assertSame('75 - Paris', $prospect->departement_label);
    }
}
