<?php

namespace Tests\Unit;

use App\Support\FailureReason;
use Tests\TestCase;

class FailureReasonTest extends TestCase
{
    public function test_it_redacts_urls_and_internal_paths(): void
    {
        $reason = FailureReason::sanitize(
            'Failed at C:\\secret\\document.pdf using https://example.test/private?signature=secret',
        );

        $this->assertStringNotContainsString('C:\\secret', $reason);
        $this->assertStringNotContainsString('https://', $reason);
        $this->assertStringNotContainsString('signature=secret', $reason);
        $this->assertStringContainsString('[redacted]', $reason);
    }

    public function test_unknown_exceptions_use_a_generic_reason(): void
    {
        $reason = FailureReason::fromThrowable(new \RuntimeException('database path C:\\secret'));

        $this->assertSame('The document could not be processed.', $reason);
    }
}
