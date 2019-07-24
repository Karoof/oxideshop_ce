<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Module\Configuration\Exception\ExtensionNotInChainException;
use OxidEsales\EshopCommunity\Internal\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;

/**
 * @internal
 */
class ClassExtensionsChain implements \IteratorAggregate
{
    const NAME = 'classExtensions';

    /**
     * @var array
     */
    private $chain = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    public function getChain(): array
    {
        return $this->chain;
    }

    /**
     * @param array $chain
     * @return ClassExtensionsChain
     */
    public function setChain(array $chain): ClassExtensionsChain
    {
        $this->chain = $chain;
        return $this;
    }

    /**
     * @param ClassExtension[] $extensions
     *
     * @return void
     */
    public function addExtensions(array $extensions) : void
    {
        foreach ($extensions as $extension) {
            $this->addExtensionToChain($extension);
        }
    }

    /**
     * @param ClassExtension $classExtension
     *
     * @throws ExtensionNotInChainException
     */
    public function removeExtension(ClassExtension $classExtension): void
    {
        $extended = $classExtension->getShopClassNamespace();
        $extension = $classExtension->getModuleExtensionClassNamespace();
        
        if (false === array_key_exists($extended, $this->chain) ||
            false === \array_search($extension, $this->chain[$extended], true)) {
            throw new ExtensionNotInChainException(
                'There is no class ' . $extended . ' extended by class ' .
                $extension . ' in the current chain'
            );
        }

        $resultOffset = \array_search($extension, $this->chain[$extended], true);
        unset($this->chain[$extended][$resultOffset]);
        $this->chain[$extended] = \array_values($this->chain[$extended]);

        if (empty($this->chain[$extended])) {
            unset($this->chain[$extended]);
        }
    }

    /**
     * @param ClassExtension $extension
     */
    public function addExtensionToChain(ClassExtension $extension): void
    {
        if (array_key_exists($extension->getShopClassNamespace(), $this->chain)) {
            array_push(
                $this->chain[$extension->getShopClassNamespace()],
                $extension->getModuleExtensionClassNamespace()
            );
        } else {
            $this->chain[$extension->getShopClassNamespace()] = [$extension->getModuleExtensionClassNamespace()];
        }
    }

    /**
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->chain);
    }
}
