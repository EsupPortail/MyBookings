<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Generate CSS variables based on configuration parameters
 */
class ThemeService
{
    public function __construct(
        private readonly ParameterBagInterface $params
    ) {
    }

    /**
     * get theme colors from parameters
     * 
     * @return array<string, string>
     */
    public function getThemeColors(): array
    {
        return $this->params->get('theme_colors');
    }

    /**
     * Get specific color by name
     * 
     * @param string $colorName (primary, secondary, etc.)
     * @return string|null color value or null if not found
     */
    public function getColor(string $colorName): ?string
    {
        $colors = $this->getThemeColors();
        return $colors[$colorName] ?? null;
    }

    /**
     * generate CSS variables from theme colors
     * used in Twig templates
     * @return string css variables
     */
    public function generateCssVariables(): string
    {
        $colors = $this->getThemeColors();
        $css = ':root {' . PHP_EOL;
        
        foreach ($colors as $name => $value) {
            $css .= "  --q-{$name}: {$value};" . PHP_EOL;
        }
        
        $css .= '}';
        
        return $css;
    }
}

