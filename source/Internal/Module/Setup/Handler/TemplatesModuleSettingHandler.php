<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Common\Exception\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration;

/**
 * @internal
 */
class TemplatesModuleSettingHandler implements ModuleConfigurationHandlerInterface
{
    /**
     * @var string
     */
    private $shopConfigurationSettingName;

    /**
     * @var ShopConfigurationSettingDaoInterface
     */
    private $shopConfigurationSettingDao;

    /**
     * @param string $shopConfigurationSettingName
     * @param ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
     */
    public function __construct(
        string $shopConfigurationSettingName,
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
    ) {
        $this->shopConfigurationSettingName = $shopConfigurationSettingName;
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int $shopId
     */
    public function handleOnModuleActivation(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasTemplates()) {
            $templates = [];

            foreach ($configuration->getTemplates() as $template) {
                $templates[$template->getTemplateKey()] = $template->getTemplatePath();
            }

            $shopConfigurationSetting = $this->getShopConfigurationSetting($shopId);

            $shopSettingValue = array_merge(
                $shopConfigurationSetting->getValue(),
                [
                    $configuration->getId() => $templates,
                ]
            );

            $shopConfigurationSetting->setValue($shopSettingValue);

            $this->shopConfigurationSettingDao->save($shopConfigurationSetting);
        }
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int $shopId
     */
    public function handleOnModuleDeactivation(ModuleConfiguration $configuration, int $shopId)
    {
        if ($configuration->hasTemplates()) {
            $shopConfigurationSetting = $this->getShopConfigurationSetting($shopId);

            $shopSettingValue = $shopConfigurationSetting->getValue();
            unset($shopSettingValue[$configuration->getId()]);

            $shopConfigurationSetting->setValue($shopSettingValue);

            $this->shopConfigurationSettingDao->save($shopConfigurationSetting);
        }
    }

    /**
     * @param int $shopId
     * @return ShopConfigurationSetting
     */
    private function getShopConfigurationSetting(int $shopId): ShopConfigurationSetting
    {
        try {
            $shopConfigurationSetting = $this->shopConfigurationSettingDao->get(
                $this->shopConfigurationSettingName,
                $shopId
            );
        } catch (EntryDoesNotExistDaoException $exception) {
            $shopConfigurationSetting = new ShopConfigurationSetting();
            $shopConfigurationSetting
                ->setShopId($shopId)
                ->setName($this->shopConfigurationSettingName)
                ->setType(ShopSettingType::ARRAY)
                ->setValue([]);
        }

        return $shopConfigurationSetting;
    }
}
