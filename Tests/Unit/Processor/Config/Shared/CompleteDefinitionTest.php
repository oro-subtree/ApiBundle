<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fieldTypeHelper;

    /** @var CompleteDefinition */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->exclusionProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface');
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationManager = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldTypeHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteDefinition(
            $this->doctrineHelper,
            $this->exclusionProvider,
            $this->configProvider,
            $this->associationManager,
            $this->fieldTypeHelper
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

    public function testProcessFieldForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessCompletedAssociationForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class'     => 'Test\Association1Target',
                    'exclusion_policy' => 'all'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id']
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithoutConfigForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($this->createRelationConfigObject());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class' => 'Test\Association1Target'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'exclusion_policy'       => 'all',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithDataTypeForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithCompositeIdForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'collapse'               => true,
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @param array|null $definition
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }

        return $config;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessFieldsForManageableEntity()
    {
        $config = [
            'fields' => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field5' => [
                    'exclude' => false
                ],
                'field6' => [
                    'property_path' => 'realField6'
                ],
                'field7' => [
                    'property_path' => 'realField7'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->exclusionProvider->expects($this->exactly(6))
            ->method('isIgnoredField')
            ->willReturnMap(
                [
                    [$rootEntityMetadata, 'id', false],
                    [$rootEntityMetadata, 'field1', false],
                    [$rootEntityMetadata, 'field3', true],
                    [$rootEntityMetadata, 'field4', false],
                    [$rootEntityMetadata, 'realField6', false],
                    [$rootEntityMetadata, 'realField7', true],
                ]
            );

        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(
                [
                    'id',
                    'field1',
                    'field2',
                    'field3',
                    'field4',
                    'field5',
                    'realField6',
                    'realField7',
                ]
            );
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

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
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => null,
                    'field5' => null,
                    'field6' => [
                        'property_path' => 'realField6'
                    ],
                    'field7' => [
                        'exclude'       => true,
                        'property_path' => 'realField7'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessCompletedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'identifier_field_names' => ['id']
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithoutConfigForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($this->createRelationConfigObject());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNewAssociationForManageableEntity()
    {
        $config = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessRenamedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'property_path' => 'realAssociation1'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['realAssociation1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'realAssociation1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'property_path'          => 'realAssociation1',
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessExcludedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclude' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNotExcludedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclude' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIgnoredAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(true);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithDataTypeForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'string'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithCompositeIdForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyForManageableEntity()
    {
        $config = [
            'fields' => [
                'id'     => null,
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => [
                    'property_path' => 'realField3'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyForManageableEntityWithIgnoredPropertyPath()
    {
        $config = [
            'fields' => [
                'id'     => null,
                'field1' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ],
                'field2' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWhenNoIdFieldInConfigForManageableEntity()
    {
        $config = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWithRenamedIdFieldInConfigForManageableEntity()
    {
        $config = [
            'fields' => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['renamedId'],
                'fields'                 => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyForNotManageableEntity()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'        => null,
                '__class__' => [
                    'meta_property' => true,
                    'data_type'     => 'string'
                ],
                'field1'    => null,
                'field2'    => [
                    'exclude' => true
                ],
                'field3'    => [
                    'property_path' => 'realField3'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => [
                        'meta_property' => true,
                        'data_type'     => 'string'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWithRenamedIdFieldInConfigForNotManageableEntity()
    {
        $config = [
            'identifier_field_names' => ['renamedId'],
            'fields'                 => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['renamedId'],
                'fields'                 => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessTableInheritanceEntity()
    {
        $config = [
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => [
                        'meta_property' => true,
                        'data_type'     => 'string'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessTableInheritanceEntityWhenClassNameFieldAlreadyExists()
    {
        $config = [
            'fields' => [
                '__class__' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessTableInheritanceEntityWhenClassNameFieldAlreadyExistsAndRenamed()
    {
        $config = [
            'fields' => [
                'type' => [
                    'property_path' => '__class__'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'   => null,
                    'type' => [
                        'property_path' => '__class__'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessTableInheritanceAssociation()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);


        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id'        => null,
                            '__class__' => null
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id'        => null,
                            '__class__' => null
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessToOneExtendedAssociationWithoutAssociationKind()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessToManyExtendedAssociationWithAssociationKind()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToMany:kind'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToMany', 'kind')
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToMany:kind',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessMultipleManyToOneExtendedAssociation()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:multipleManyToOne'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'multipleManyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:multipleManyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessExtendedAssociationWithCustomTargetClass()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'    => 'association:manyToOne',
                    'target_class' => 'Test\TargetClass',
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\TargetClass',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => 'Test\TargetClass',
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "target_type" option cannot be configured for "Test\Class::association1".
     */
    public function testProcessExtendedAssociationWithCustomTargetType()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'   => 'association:manyToOne',
                    'target_type' => 'to-many',
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

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
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "depends_on" option cannot be configured for "Test\Class::association1".
     */
    public function testProcessExtendedAssociationWithCustomDependsOn()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'  => 'association:manyToOne',
                    'depends_on' => ['field1'],
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

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
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessExtendedAssociationWhenTargetsHaveNotStringIdentifier()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target2EntityMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata],
                ]
            );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1', 'field2'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessExtendedAssociationWhenTargetsHaveDifferentTypesOfIdentifier()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target2EntityMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('string');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata],
                ]
            );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1', 'field2'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessExtendedAssociationWhenOneOfTargetHasCombinedIdentifier()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);
        $target2EntityMetadata->expects($this->never())
            ->method('getTypeOfField');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata],
                ]
            );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                EntityIdentifier::class,
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1', 'field2'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'data_class' => 'Test\Target'
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'data_class'    => 'Test\Target',
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectForManageableEntity()
    {
        $config = [
            'fields' => [
                'id'     => null,
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'data_class' => 'Test\Target'
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field1', 'field2']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

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
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'data_class'    => 'Test\Target',
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectForRenamedField()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'data_type'    => 'nestedObject',
                    'form_options' => [
                        'data_class'    => 'Test\Target',
                        'property_path' => 'otherField'
                    ],
                    'fields'       => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'data_class'    => 'Test\Target',
                            'property_path' => 'otherField'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectWithoutFormOptions()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'data_type' => 'nestedObject',
                    'fields'    => [
                        'field11' => [
                            'property_path' => 'field2'
                        ]
                    ]
                ],
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field2']
                    ],
                    'field2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectWhenDependsOnIsPartiallySet()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'data_type'  => 'nestedObject',
                    'depends_on' => ['field3'],
                    'fields'     => [
                        'field11' => [
                            'property_path' => 'field2'
                        ],
                        'field12' => [
                            'property_path' => 'field3'
                        ]
                    ]
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'nestedObject',
                        'form_options'     => [
                            'property_path' => 'field1'
                        ],
                        'fields'           => [
                            'field11' => [
                                'property_path' => 'field2'
                            ],
                            'field12' => [
                                'property_path' => 'field3'
                            ]
                        ],
                        'property_path'    => ConfigUtil::IGNORE_PROPERTY_PATH,
                        'exclusion_policy' => 'all',
                        'depends_on'       => ['field3', 'field2']
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "target_type" option cannot be configured for "Test\Class::association1".
     */
    public function testProcessExtendedInverseAssociationWithCustomTargetType()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'   => 'inverseAssociation:Acme/DemoBundle/Entity:manyToOne',
                    'target_type' => 'to-many',
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

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
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "depends_on" option cannot be configured for "Test\Class::association1".
     */
    public function testProcessExtendedInverseAssociationWithCustomDependsOn()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'  => 'inverseAssociation:Acme/DemoBundle/Entity:manyToOne',
                    'depends_on' => ['field1'],
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

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
    }

    public function testProcessExtendedInverseAssociation()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'inverseAssociation:Test\TargetClass:manyToOne:testKind'
                ],
            ]
        ];

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with('Test\TargetClass', null, 'manyToOne', 'testKind')
            ->willReturn(
                [
                    'Test\TargetClass1' => 'field1',
                    'Test\TargetClass2' => 'field2',
                    self::TEST_CLASS_NAME => 'field3'
                ]
            );
        $this->fieldTypeHelper->expects($this->once())
            ->method('getUnderlyingType')
            ->with('manyToOne')
            ->willReturn('manyToOne');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\TargetClass',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'inverseAssociation:Test\TargetClass:manyToOne:testKind',
                        'target_class'           => 'Test\TargetClass',
                        'target_type'            => 'to-many',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ],
                        'association-field' => 'field3',
                        'association-kind' => 'testKind'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
