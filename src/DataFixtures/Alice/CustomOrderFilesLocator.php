<?php declare(strict_types=1);

namespace App\DataFixtures\Alice;

use Hautelook\AliceBundle\FixtureLocatorInterface;
use Nelmio\Alice\IsAServiceTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CustomOrderFilesLocator implements FixtureLocatorInterface
{
    use IsAServiceTrait;

    public function __construct(private FixtureLocatorInterface $decoratedFixtureLocator, #[Autowire('%platform_mode%')] private string $platformMode)
    {
    }

    /**
     * Re-order the files found by the decorated finder.
     *
     * {@inheritdoc}
     */
    public function locateFiles(array $bundles, string $environment): array
    {
        $files = $this->decoratedFixtureLocator->locateFiles($bundles, $environment);

        // TODO: order the files found in whatever order you want

        // Warning: the order will only affect how the fixture definitions are merged. Indeed the order in which they
        // are instantiated afterwards by nelmio/alice may change due to handling the fixture dependencies and
        // circular references.
        if($environment === 'test') {
            return $files;
        }

        $newFileOrder = [];
        $platformSearchType = 'demo' === $this->platformMode ? 'demo' : 'ldap';
        foreach ($files as $file) {
            if (!str_contains($file, 'group') || (str_contains($file, 'group') && str_contains($file, $platformSearchType))) {
                $newFileOrder[] = $file;
            }
        }

        return $newFileOrder;
    }
}