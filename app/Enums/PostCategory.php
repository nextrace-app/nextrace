<?php

namespace App\Enums;

enum PostCategory: string
{
    case News = 'news';
    case Guide = 'guide';
    case Results = 'results';
    case Interview = 'interview';
}
