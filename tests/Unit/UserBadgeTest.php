<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\UserBadge;
use PHPUnit\Framework\TestCase;

class UserBadgeTest extends TestCase
{
    public function test_resolve_color_returns_preset_hex(): void
    {
        $this->assertSame('#dc3545', UserBadge::resolveColor('red', null));
        $this->assertSame('#7b1e1e', UserBadge::resolveColor('bordeaux', null));
        $this->assertSame('#28a745', UserBadge::resolveColor('green', null));
    }

    public function test_resolve_color_uses_custom_when_preset_is_custom(): void
    {
        $this->assertSame('#123abc', UserBadge::resolveColor('custom', '#123abc'));
    }

    public function test_resolve_color_falls_back_when_custom_empty(): void
    {
        $this->assertSame('#28a745', UserBadge::resolveColor('custom', null));
        $this->assertSame('#28a745', UserBadge::resolveColor('custom', ''));
        $this->assertSame('#28a745', UserBadge::resolveColor('unknown', null));
    }

    public function test_resolve_label_returns_presets(): void
    {
        $this->assertSame('Подозрительная личность', UserBadge::resolveLabel('suspicious', null));
        $this->assertSame('', UserBadge::resolveLabel('none', null));
        $this->assertSame('Подтверждён', UserBadge::resolveLabel('confirmed', null));
    }

    public function test_resolve_label_uses_trimmed_custom(): void
    {
        $this->assertSame('VIP клиент', UserBadge::resolveLabel('custom', '  VIP клиент  '));
        $this->assertSame('', UserBadge::resolveLabel('custom', null));
    }
}
