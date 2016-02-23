<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteSorters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteSortersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var CompleteSorters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteSorters($this->doctrineHelper);
    }

    public function testProcessForAlreadyCompletedSorters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => null,
                'field4' => null,
            ]
        ];

        $sorters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude' => true
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testProcessForNotCompletedSortersButForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $sorters = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getSorters()
        );
    }

    public function testProcessForNotCompletedSorters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'field3'       => null,
                'field4'       => null,
                'field5'       => null,
                'association1' => null,
            ]
        ];

        $sorters = [
            'fields' => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude' => true
                ],
            ]
        ];

        $rootEntityMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs([self::TEST_CLASS_NAME])
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects($this->once())
            ->method('getIndexedFields')
            ->with($this->identicalTo($rootEntityMetadata))
            ->willReturn(
                [
                    'field5' => 'integer',
                    'field6' => 'integer',
                ]
            );
        $this->doctrineHelper->expects($this->once())
            ->method('getIndexedAssociations')
            ->with($this->identicalTo($rootEntityMetadata))
            ->willReturn(
                [
                    'association1' => 'integer',
                    'association2' => 'integer',
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'       => null,
                    'field2'       => [
                        'exclude' => true
                    ],
                    'field3'       => [
                        'exclude' => true
                    ],
                    'field5'       => null,
                    'association1' => null,
                ]
            ],
            $this->context->getSorters()
        );
    }
}
