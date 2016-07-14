<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Component\Config\Common\ConfigObject;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class CheckoutGridAccountUserNameListener
{
    /**
     * @var AccountUserProvider
     */
    private $accountUserProvider;

    /**
     * @param AccountUserProvider $accountUserProvider
     */
    public function __construct(
        AccountUserProvider $accountUserProvider
    ) {
        $this->accountUserProvider = $accountUserProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]');

        if (isset($columns['accountUserName']) && !$this->hasPermissionToViewAllPastCheckouts()) {
            $config->offsetUnsetByPath('[columns][accountUserName]');
            $config->offsetUnsetByPath('[sorters][columns][accountUserName]');
        }
    }

    /**
     * @return bool
     */
    private function hasPermissionToViewAllPastCheckouts()
    {
        return $this->accountUserProvider->isGrantedViewLocal('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }
}
