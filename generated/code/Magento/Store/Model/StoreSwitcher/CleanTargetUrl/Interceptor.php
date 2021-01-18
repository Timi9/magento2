<?php
namespace Magento\Store\Model\StoreSwitcher\CleanTargetUrl;

/**
 * Interceptor class for @see \Magento\Store\Model\StoreSwitcher\CleanTargetUrl
 */
class Interceptor extends \Magento\Store\Model\StoreSwitcher\CleanTargetUrl implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Url\Helper\Data $urlHelper)
    {
        $this->___init();
        parent::__construct($urlHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function switch(\Magento\Store\Api\Data\StoreInterface $fromStore, \Magento\Store\Api\Data\StoreInterface $targetStore, string $redirectUrl) : string
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'switch');
        return $pluginInfo ? $this->___callPlugins('switch', func_get_args(), $pluginInfo) : parent::switch($fromStore, $targetStore, $redirectUrl);
    }
}
