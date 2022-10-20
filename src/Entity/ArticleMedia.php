<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ArticleMedia extends Media
{
    const DEFAULT_PUBLIC_PATH = 'media/';
}
