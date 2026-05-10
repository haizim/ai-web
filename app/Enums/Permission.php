<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @extends Enum<string>
 */
final class Permission extends Enum
{
    // sample permission
    // const POST_MANAGE = 'post.manage';

    // Styles
    const STYLES_VIEW = 'styles:view';

    const STYLES_MANAGE = 'styles:manage';
}
