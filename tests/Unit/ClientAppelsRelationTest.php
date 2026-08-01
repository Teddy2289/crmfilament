<?php

namespace Tests\Unit;

use App\Models\Appel;
use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PHPUnit\Framework\Attributes\Test;

class ClientAppelsRelationTest
{
    #[Test]
    public function it_has_a_morph_many_appels_relation(): void
    {
        $client = new Client();

        $relation = $client->appels();

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertSame(Appel::class, $relation->getRelated()::class);
    }
}
