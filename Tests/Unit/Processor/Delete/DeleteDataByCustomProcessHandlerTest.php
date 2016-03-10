<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteDataByCustomProcessHandler;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;

class DeleteDataByCustomProcessHandlerTest extends DeleteDataByProcessHandlerTest
{
    protected $supportedClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

    protected function buildProcessor()
    {
        $this->processor = new DeleteDataByCustomProcessHandler(
            $this->doctrineHelper,
            $this->deleteHandler,
            $this->supportedClassName
        );
    }

    public function testProcessOnNonSupportedObject()
    {
        $this->context->setObject(new \stdClass());
        $this->deleteHandler->expects($this->never())
            ->method('processDelete');
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $object = new Product();
        $this->context->setObject($object);

        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($object)
            ->willReturn($em);

        $this->deleteHandler->expects($this->once())
            ->method('processDelete')
            ->with($object, $em);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasObject());
    }
}
