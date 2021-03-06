<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\MessageQueueBundle\Consumption\Exception\InvalidSecurityTokenException;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The message consumption extension that replaces a current security token with the token
 * that is contained in a current message.
 * This provides an ability to process a message in the same security context
 * as a process that sent the message.
 * Also the "security_agnostic_processors" option can be used to disable changing the security context
 * for some processors.
 * For details see "Resources/doc/security_context.md".
 */
class SecurityAwareConsumptionExtension extends AbstractExtension
{
    /** @var array [processor name => TRUE, ...] */
    private $securityAgnosticProcessors;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenSerializerInterface */
    private $tokenSerializer;

    /**
     * @param string[]                 $securityAgnosticProcessors
     * @param TokenStorageInterface    $tokenStorage
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function __construct(
        array $securityAgnosticProcessors,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer
    ) {
        $this->securityAgnosticProcessors = array_fill_keys($securityAgnosticProcessors, true);
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if (isset($this->securityAgnosticProcessors[$context->getMessageProcessorName()])) {
            return;
        }

        // check whether a current message should be executed in own security context,
        // and if so, switch to the requested context
        $serializedToken = $context->getMessage()->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN);
        if ($serializedToken) {
            $token = $this->tokenSerializer->deserialize($serializedToken);
            if (null === $token) {
                $exception = new InvalidSecurityTokenException();
                $context->getLogger()->error($exception->getMessage());

                throw $exception;
            } else {
                $context->getLogger()->debug('Set security token');
                $this->tokenStorage->setToken($token);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        // reset the security context after processing of each message
        $this->tokenStorage->setToken(null);
    }
}
