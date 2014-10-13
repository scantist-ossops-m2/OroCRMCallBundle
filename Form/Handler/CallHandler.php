<?php

namespace OroCRM\Bundle\CallBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

use OroCRM\Bundle\CallBundle\Entity\Call;
use OroCRM\Bundle\CallBundle\Entity\Manager\CallActivityManager;
use OroCRM\Bundle\CallBundle\Model\PhoneHolderInterface;

class CallHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var string
     */
    protected $formName;

    /**
     * @var string
     */
    protected $formType;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var CallActivityManager
     */
    protected $callActivityManager;

    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @param string $formName
     * @param string $formType
     * @param Request       $request
     * @param ObjectManager $manager
     * @param CallActivityManager $callActivityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param FormFactory $formFactory
     */
    public function __construct(
        $formName,
        $formType,
        Request $request,
        ObjectManager $manager,
        CallActivityManager $callActivityManager,
        EntityRoutingHelper $entityRoutingHelper,
        FormFactory $formFactory
    ) {
        $this->formName = $formName;
        $this->formType = $formType;
        $this->request = $request;
        $this->manager = $manager;
        $this->callActivityManager = $callActivityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->formFactory = $formFactory;
    }

    /**
     * Process form
     *
     * @param  Call $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(Call $entity)
    {
        $target = $this->getTargetEntity();

        if ($target && $target instanceof PhoneHolderInterface) {
            $options = [
                'suggestions' => $target->getPhoneNumbers(),
                'default_choice' => $target->getPrimaryPhoneNumber(),
            ];
        } else {
            $options = [
                'suggestions' => (array) $entity->getPhoneNumber(),
                'default_choice' => $entity->getPhoneNumber(),
            ];
        }

        $this->form = $this->formFactory->createNamed($this->formName, $this->formType, $entity, $options);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {

                if ($target) {
                    $this->callActivityManager->addAssociation($entity, $target);
                }

                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    /**
     * Get object of activity owner
     *
     * @return object|null
     */
    protected function getTargetEntity()
    {
        /** @var string $entityClass */
        $entityClass = $this->entityRoutingHelper->decodeClassName(
            $this->request->get('entityClass')
        );
        /** @var integer $entityId */
        $entityId = $this->request->get('entityId');

        if ($entityClass && $entityId) {
            $entity = $this->manager->getRepository($entityClass)->find($entityId);
        } else {
            $entity = null;
        }

        return $entity;
    }

    /**
     * "Success" form handler
     *
     * @param Call $entity
     */
    protected function onSuccess(Call $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * Get form, that build into handler, via handler service
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }
}
