<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Form\FormExtensionSwitcherInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;

/**
 * Switches to Data API form extension.
 */
class InitializeApiFormExtension implements ProcessorInterface
{
    /** @var FormExtensionSwitcherInterface */
    protected $formExtensionSwitcher;

    /** @var MetadataTypeGuesser */
    protected $metadataTypeGuesser;

    /**
     * @param FormExtensionSwitcherInterface $formExtensionSwitcher
     * @param MetadataTypeGuesser            $metadataTypeGuesser
     */
    public function __construct(
        FormExtensionSwitcherInterface $formExtensionSwitcher,
        MetadataTypeGuesser $metadataTypeGuesser
    ) {
        $this->formExtensionSwitcher = $formExtensionSwitcher;
        $this->metadataTypeGuesser = $metadataTypeGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $this->formExtensionSwitcher->switchToApiFormExtension();

        /**
         * remember current metadata and config accessors as an action can be nested
         * and accessors should be restored after the current action
         * @see \Oro\Bundle\ApiBundle\Processor\Shared\RestoreDefaultFormExtension
         */
        $currentMetadataAccessor = $this->metadataTypeGuesser->getMetadataAccessor();
        if (null !== $currentMetadataAccessor) {
            $context->set('previousMetadataAccessor', $currentMetadataAccessor);
        }
        $currentConfigAccessor = $this->metadataTypeGuesser->getConfigAccessor();
        if (null !== $currentConfigAccessor) {
            $context->set('previousConfigAccessor', $currentConfigAccessor);
        }

        // set metadata and config accessors
        $this->metadataTypeGuesser->setMetadataAccessor(new ContextMetadataAccessor($context));
        $this->metadataTypeGuesser->setConfigAccessor(new ContextConfigAccessor($context));
    }
}
