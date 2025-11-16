<?php

namespace App\Enum;

enum WowGameType: string
{
    case RETAIL = 'retail';
    case CLASSIC_ERA = 'classic-era';
    case CLASSIC_PROGRESSION = 'classic-progression';
    case CLASSIC_ANNIVERSARY = 'classic-anniversary';
    case SEASON_OF_DISCOVERY = 'season-of-discovery';
    case HARDCORE = 'hardcore';

    public function getLabel(): string
    {
        return match($this) {
            self::RETAIL => 'Retail',
            self::CLASSIC_ERA => 'Classic Era',
            self::CLASSIC_PROGRESSION => 'Classic Progression',
            self::CLASSIC_ANNIVERSARY => 'Classic Anniversary',
            self::SEASON_OF_DISCOVERY => 'Season of Discovery',
            self::HARDCORE => 'Hardcore',
        };
    }

    
    public function getProfileNamespace(string $region): string
    {
        return match($this) {
            self::RETAIL => "profile-{$region}",
            self::CLASSIC_ERA => "profile-classic-{$region}",
            self::CLASSIC_PROGRESSION => "profile-classic-{$region}",
            self::CLASSIC_ANNIVERSARY => "profile-classic1x-{$region}",
            self::SEASON_OF_DISCOVERY => "profile-classic1x-{$region}",
            self::HARDCORE => "profile-classic1x-{$region}",
        };
    }

    
    public function getDynamicNamespace(string $region): string
    {
        return match($this) {
            self::RETAIL => "dynamic-{$region}",
            self::CLASSIC_ERA => "dynamic-classic-{$region}",
            self::CLASSIC_PROGRESSION => "dynamic-classic-{$region}",
            self::CLASSIC_ANNIVERSARY => "dynamic-classic1x-{$region}",
            self::SEASON_OF_DISCOVERY => "dynamic-classic1x-{$region}",
            self::HARDCORE => "dynamic-classic1x-{$region}",
        };
    }

    
    public function getStaticNamespace(string $region): string
    {
        return match($this) {
            self::RETAIL => "static-{$region}",
            self::CLASSIC_ERA => "static-classic-{$region}",
            self::CLASSIC_PROGRESSION => "static-classic-{$region}",
            self::CLASSIC_ANNIVERSARY => "static-classic1x-{$region}",
            self::SEASON_OF_DISCOVERY => "static-classic1x-{$region}",
            self::HARDCORE => "static-classic1x-{$region}",
        };
    }

    
    public static function fromString(string $wowType): self
    {
        return match(true) {
            str_contains(strtolower($wowType), 'classic anniversary') => self::CLASSIC_ANNIVERSARY,
            str_contains(strtolower($wowType), 'classic era') => self::CLASSIC_ERA,
            str_contains(strtolower($wowType), 'season of discovery') => self::SEASON_OF_DISCOVERY,
            str_contains(strtolower($wowType), 'hardcore') => self::HARDCORE,
            str_contains(strtolower($wowType), 'classic') => self::CLASSIC_PROGRESSION,
            default => self::RETAIL,
        };
    }
}
