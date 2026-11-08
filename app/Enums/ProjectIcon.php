<?php

namespace App\Enums;

/**
 * The icons a project can show. Each value is the name of a Lucide icon the front end imports.
 */
enum ProjectIcon: string
{
    case Folder = 'folder';
    case Rocket = 'rocket';
    case Code = 'code';
    case Palette = 'palette';
    case Megaphone = 'megaphone';
    case Book = 'book';
    case Briefcase = 'briefcase';
    case Heart = 'heart';
    case Star = 'star';
    case Zap = 'zap';
}
