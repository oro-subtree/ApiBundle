<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

class CreateContext extends Context implements FormContext
{
    use FormContextTrait;
}
