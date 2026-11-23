<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_masked_nim_keeps_only_the_first_and_last_two_characters(): void
    {
        $this->assertSame('20••••01', (new User(['nim' => '20260001']))->maskedNim());
        $this->assertSame('12•45', (new User(['nim' => '12345']))->maskedNim());
    }

    public function test_masked_nim_hides_short_values_completely(): void
    {
        $this->assertSame('••••', (new User(['nim' => '1234']))->maskedNim());
        $this->assertSame('•••', (new User(['nim' => '123']))->maskedNim());
    }

    public function test_masked_nim_is_null_without_a_nim(): void
    {
        $this->assertNull((new User)->maskedNim());
        $this->assertNull((new User(['nim' => '']))->maskedNim());
    }
}
