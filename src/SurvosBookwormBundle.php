<?php
declare(strict_types=1);
namespace Survos\BookwormBundle;

use Survos\BookwormBundle\Command\BookwormService;
use Survos\BookwormBundle\Corpus\CorpusRegistry;
use Survos\BookwormBundle\Document\{MarkdownChunkTransformer,MarkdownCorpusLoader};
use Survos\BookwormBundle\MessageHandler\IndexCorpusHandler;
use Survos\BookwormBundle\Service\CorpusIndexer;
use Survos\BookwormBundle\Source\GitHubFetcher;
use Survos\Kit\{AbstractSurvosBundle,SurvosKitBundle};
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\{ContainerBuilder,ContainerInterface,Reference};
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\AI\Store\Document\Loader\MarkdownLoader;

#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosBookwormBundle extends AbstractSurvosBundle
{
    protected function twigNamespace(): ?string { return null; }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()->arrayNode('corpora')->useAttributeAsKey('name')
            ->arrayPrototype()->children()
                ->scalarNode('directory')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('indexer')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('repository')->defaultNull()->end()
                ->scalarNode('canonical_base_url')->defaultNull()->end()
                ->arrayNode('patterns')->scalarPrototype()->end()->defaultValue(['*.md'])->end()
                ->integerNode('max_chunk_size')->min(1)->defaultValue(4000)->end()
            ->end()->end()->defaultValue([])->end()->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set(CorpusRegistry::class)->arg('$config', $config['corpora']);
        $services->set(MarkdownLoader::class);
        $services->set(MarkdownCorpusLoader::class); $services->set(MarkdownChunkTransformer::class);
        $indexers = [];
        foreach ($config['corpora'] as $corpus) { $indexers[$corpus['indexer']] = new Reference($corpus['indexer']); }
        $services->set(CorpusIndexer::class)->arg('$indexers', new ServiceLocatorArgument($indexers));
        $services->set(IndexCorpusHandler::class);
        if (class_exists(\Github\Client::class)) {
            $services->set(\Github\Client::class)->autowire(false)->autoconfigure(false);
            $services->set(Filesystem::class);
            $services->set(GitHubFetcher::class);
        }
        $services->set(BookwormService::class)
            ->arg('$githubFetcher', new Reference(GitHubFetcher::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->arg('$projectDir', '%kernel.project_dir%');
    }
}
