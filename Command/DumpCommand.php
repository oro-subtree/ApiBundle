<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class DumpCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:dump')
            ->setDescription('Dumps all resources available through Data API.')
            // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //)
            ->addOption(
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The request type',
                [RequestType::REST, RequestType::JSON_API]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $requestType = $input->getOption('request-type');
        // @todo: API version is not supported for now
        //$version     = $input->getArgument('version');
        $version = Version::LATEST;

        /** @var ResourcesLoader $resourcesLoader */
        $resourcesLoader = $this->getContainer()->get('oro_api.resources_loader');
        $resources       = $resourcesLoader->getResources($version, $requestType);

        $table = new Table($output);
        $table->setHeaders(['Entity', 'Attributes']);

        $i = 0;
        foreach ($resources as $resource) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }
            $table->addRow(
                [
                    $resource->getEntityClass(),
                    $this->convertResourceAttributesToString($this->getResourceAttributes($resource, $requestType))
                ]
            );
            $i++;
        }

        $table->render();
    }

    /**
     * @param ApiResource $resource
     * @param string[]    $requestType
     *
     * @return array
     */
    protected function getResourceAttributes(ApiResource $resource, array $requestType)
    {
        $result = [];

        $entityClass = $resource->getEntityClass();

        /** @var ValueNormalizer $valueNormalizer */
        $valueNormalizer      = $this->getContainer()->get('oro_api.value_normalizer');
        $result['Entity Type'] = $valueNormalizer->normalizeValue(
            $entityClass,
            DataType::ENTITY_TYPE,
            $requestType
        );

        /** @var EntityClassNameProviderInterface $entityClassNameProvider */
        $entityClassNameProvider = $this->getContainer()->get('oro_entity.entity_class_name_provider');
        $result['Name']          = $entityClassNameProvider->getEntityClassName($entityClass);
        $result['Plural Name']   = $entityClassNameProvider->getEntityClassPluralName($entityClass);

        return $result;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function convertResourceAttributesToString(array $attributes)
    {
        $result = '';

        $i = 0;
        foreach ($attributes as $name => $value) {
            if ($i > 0) {
                $result .= PHP_EOL;
            }
            $result .= sprintf('%s: %s', $name, $value);
            $i++;
        }

        return $result;
    }
}
