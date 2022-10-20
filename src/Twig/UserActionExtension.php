<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Security\Core\Security;

class UserActionExtension extends AbstractExtension
{
    protected Security $security;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('action_link', [$this, 'createActionLink'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function createActionLink(string $role, string $label, string $path, array $parameters = [], array $attributes = []): string
    {
        if (!$this->security->isGranted($role)) {
            return '';
        }

        $attributesString = '';
        foreach ($attributes as $attribute => $value) {
            $attributesString .= sprintf('%s="%s", ', $attribute, $value);
        }

        return sprintf('<a href="%s" %s>%s</a>', $this->urlGenerator->generate($path, $parameters), $attributesString, $label);
    }
}
