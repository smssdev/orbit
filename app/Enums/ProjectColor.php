<?php

namespace App\Enums;

/**
 * The colours a project can wear. The front end maps each one to its own swatch, in light and dark mode.
 */
enum ProjectColor: string
{
    case Slate = 'slate';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Pink = 'pink';
}
