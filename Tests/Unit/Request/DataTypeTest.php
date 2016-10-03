<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\DataType;

class DataTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider associationAsFieldProvider
     */
    public function testIsAssociationAsField($dataType, $expected)
    {
        self::assertSame($expected, DataType::isAssociationAsField($dataType));
    }

    public function associationAsFieldProvider()
    {
        return [
            ['array', true],
            ['scalar', true],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider extendedAssociationProvider
     */
    public function testIsExtendedAssociation($dataType, $expected)
    {
        self::assertSame($expected, DataType::isExtendedAssociation($dataType));
    }

    public function extendedAssociationProvider()
    {
        return [
            ['association:manyToOne', true],
            ['association:manyToOne:kind', true],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider parseExtendedAssociationProvider
     */
    public function testParseExtendedAssociation($dataType, $expectedAssociationType, $expectedAssociationKind)
    {
        list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
        self::assertSame($expectedAssociationType, $associationType);
        self::assertSame($expectedAssociationKind, $associationKind);
    }

    public function parseExtendedAssociationProvider()
    {
        return [
            ['association:manyToOne', 'manyToOne', null],
            ['association:manyToOne:kind', 'manyToOne', 'kind'],
        ];
    }

    /**
     * @dataProvider invalidExtendedAssociationProvider
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalidExtendedAssociation($dataType)
    {
        DataType::parseExtendedAssociation($dataType);
    }

    public function invalidExtendedAssociationProvider()
    {
        return [
            ['string'],
            ['association'],
            ['association:'],
            ['association::'],
            ['association:manyToOne:'],
            [null],
            [''],
        ];
    }
}
