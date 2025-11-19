<?php

namespace App\Security;

final class OAuthSessionKeys
{
    public const DISCORD_REDIRECT = 'discord_post_login_redirect';
    public const BLIZZARD_REDIRECT = 'blizzard_post_login_redirect';

    private function __construct()
    {
    }
}
