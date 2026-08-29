<?php

namespace App\Enums;

enum ServiceExecutionChannel: string
{
    case CONSOLE = 'console';
    case QUEUE = 'queue';
    case API = 'api';
    case LEGACY_IMPORT = 'legacy_import';
}
