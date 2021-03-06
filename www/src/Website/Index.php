<?php

namespace App\Website;

use PSX\Framework\Controller\ViewAbstract;
use PSX\Http\Client\Client;
use PSX\Http\RequestInterface;
use PSX\Http\ResponseInterface;
use PSX\Schema\GeneratorFactory;
use PSX\Schema\Parser\TypeSchema;

class Index extends ViewAbstract
{
    /**
     * @Inject
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache;

    public function onGet(RequestInterface $request, ResponseInterface $response)
    {
        $item = $this->cache->getItem('example-cache');
        if (!$item->isHit()) {
            $examples = $this->getExamples();
            foreach ($examples as $key => $example) {
                $types = GeneratorFactory::getPossibleTypes();
                foreach ($types as $type) {
                    $examples[$key]['types'][$type] = $this->convert($type, $example['schema']);
                }
            }

            $item->expiresAfter(null);
            $item->set($examples);
            $this->cache->save($item);
        } else {
            $examples = $item->get();
        }

        $this->render($response, __DIR__ . '/resource/index.php', [
            'examples' => $examples
        ]);
    }

    private function convert(string $type, string $code)
    {
        $httpClient = new Client();
        $parser = new TypeSchema(TypeSchema\ImportResolver::createDefault($httpClient), __DIR__ . '/resource/examples');
        $schema = $parser->parse($code);

        $factory = new GeneratorFactory();
        $generator = $factory->getGenerator($type, null);

        return (string) $generator->generate($schema);
    }

    private function getExamples(): array
    {
        $examples = [];
        $examples[] = [
            'title' => 'Simple model',
            'description' => 'A simple model with some scalar properties.',
            'schema' => file_get_contents(__DIR__ . '/resource/examples/simple.json'),
        ];

        $examples[] = [
            'title' => 'Model with inheritance',
            'description' => 'A student class which extends from the human class.',
            'schema' => file_get_contents(__DIR__ . '/resource/examples/inheritance.json'),
        ];

        $examples[] = [
            'title' => 'Model with discriminator',
            'description' => 'A model which contains a union type.',
            'schema' => file_get_contents(__DIR__ . '/resource/examples/discriminator.json'),
        ];

        $examples[] = [
            'title' => 'Advanced model which uses generics',
            'description' => 'A generic map which uses a specific model.',
            'schema' => file_get_contents(__DIR__ . '/resource/examples/generic.json'),
        ];

        $examples[] = [
            'title' => 'Import other TypeSchema specification',
            'description' => 'Shows how to import and use another TypeSchema.',
            'schema' => file_get_contents(__DIR__ . '/resource/examples/import.json'),
        ];

        return $examples;
    }
}
