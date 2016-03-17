<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class DumpMetadataCommand extends AbstractDebugCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:api:metadata:dump')
            ->setDescription('Dumps entity metadata used in Data API.')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'The entity class name or alias'
            );
            // @todo: API version is not supported for now
            //->addArgument(
            //    'version',
            //    InputArgument::OPTIONAL,
            //    'API version',
            //    Version::LATEST
            //);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityClassNameHelper $entityClassNameHelper */
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');

        $entityClass = $entityClassNameHelper->resolveEntityClass($input->getArgument('entity'), true);
        $requestType = $this->getRequestType($input);
        // @todo: API version is not supported for now
        //$version     = $input->getArgument('version');
        $version = Version::LATEST;

        /** @var ProcessorBag $processorBag */
        $processorBag = $this->getContainer()->get('oro_api.processor_bag');
        $processorBag->addApplicableChecker(new RequestTypeApplicableChecker());

        $metadata = $this->getMetadata($entityClass, $version, $requestType);
        $output->write(Yaml::dump($metadata, 100, 4, true, true));
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function getMetadata($entityClass, $version, RequestType $requestType)
    {
        /** @var MetadataProvider $configProvider */
        $metadataProvider = $this->getContainer()->get('oro_api.metadata_provider');
        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getContainer()->get('oro_api.config_provider');

        $config   = $configProvider->getConfig($entityClass, $version, $requestType);
        $metadata = $metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            [],
            $config->getDefinition()
        );

        return [
            'oro_api' => [
                'metadata' => [
                    $entityClass => null !== $metadata ? $metadata->toArray() : null
                ]
            ]
        ];
    }
}
