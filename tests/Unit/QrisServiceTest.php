<?php

namespace Tests\Unit;

use App\Services\QrisService;
use Tests\TestCase;

class QrisServiceTest extends TestCase
{
    public function test_it_inserts_tag_54_before_country_code_and_recalculates_crc(): void
    {
        $service = new QrisService();
        $masterPayload = '0002010102115802ID5910TEST STORE6007JAKARTA6304ABCD';

        $payload = $service->generateFixedAmountPayload('99.000', $masterPayload);

        $this->assertStringContainsString('5405990005802ID', $payload);
        $this->assertSame(
            $service->calculateCrc(substr($payload, 0, -4)),
            substr($payload, -4)
        );
    }

    public function test_it_replaces_existing_tag_54_value(): void
    {
        $service = new QrisService();
        $masterPayload = '000201010211540410005802ID5910TEST STORE6007JAKARTA6304ABCD';

        $payload = $service->generateFixedAmountPayload('1000000', $masterPayload);

        $this->assertStringContainsString('54071000000', $payload);
        $this->assertStringNotContainsString('54041000', $payload);
    }
}
