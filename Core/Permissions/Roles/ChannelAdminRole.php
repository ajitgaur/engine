<?php

namespace Minds\Core\Permissions\Roles;

class ChannelAdminRole extends BaseRole
{
    public function __construct()
    {
        parent::__construct(Roles::ROLE_CHANNEL_ADMIN);
    }
}
