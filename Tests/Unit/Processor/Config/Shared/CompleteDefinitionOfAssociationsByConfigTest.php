<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinitionOfAssociationsByConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class CompleteDefinitionOfAssociationsByConfigTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $relationConfigProvider;

    /** @var CompleteDefinitionOfAssociationsByConfig */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper         = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Provider\RelationConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteDefinitionOfAssociationsByConfig(
            $this->doctrineHelper,
            $this->relationConfigProvider
        );
    }

    public function testProcessForAlreadyProcessedConfig()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'fields' => [
                'association4' => [
                    'exclusion_policy' => 'all'
                ],
            ]
        ];

        $extra1 = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigExtraInterface');
        $extra2 = new TestConfigSection('test_section');
        $this->context->setExtras([$extra1, $extra2]);

        $rootEntityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs([self::TEST_CLASS_NAME])
            ->getMock();
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target'
                    ],
                    'association2' => [
                        'targetEntity' => 'Test\Association2Target'
                    ],
                    'association3' => [
                        'targetEntity' => 'Test\Association3Target'
                    ],
                    'association4' => [
                        'targetEntity' => 'Test\Association4Target'
                    ],
                ]
            );

        $this->relationConfigProvider->expects($this->exactly(3))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getExtras(),
                        $this->createRelationConfigObject()
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'id' => null
                                ]
                            ],
                            ['test']
                        )
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'collapse'         => true,
                                'fields'           => [
                                    'id' => null
                                ]
                            ]
                        )
                    ],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => null
                        ],
                        'test_section'     => ['test']
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association4' => [
                        'exclusion_policy' => 'all'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @param array|null $definition
     * @param array|null $testSection
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null, array $testSection = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }
        if (null !== $testSection) {
            $config->set('test_section', $testSection);
        }

        return $config;
    }
}
