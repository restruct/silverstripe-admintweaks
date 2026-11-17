<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use ArrayObject;
use SilverStripe\Core\Injector\Injector;

/**
 * HTMLEditorConfig's registry of named configs (its protected static $configs), as an ArrayObject
 * that builds the `minimal` config the first time it is looked up (admintweaks#116). See
 * MinimalEditorConfig for why, and MinimalEditorConfig::installLazyRegistry() for the swap.
 *
 * HTMLEditorConfig (framework 4 and 5) only uses the registry with isset(), [] read/write, unset()
 * and foreach, all of which ArrayObject supports; every name other than `minimal` passes straight
 * through.
 */
class MinimalEditorConfigRegistry extends ArrayObject
{
    /**
     * True while the copy is being built, so a lookup of `minimal` from inside the build (none is
     * expected) cannot recurse.
     *
     * @var bool
     */
    private $building = false;

    /**
     * isset($configs['minimal']) in HTMLEditorConfig::get() is "first requested": build it then.
     *
     * @param mixed $key
     */
    public function offsetExists($key): bool
    {
        $this->buildMinimalIfMissing($key);
        return parent::offsetExists($key);
    }

    /**
     * A direct read of `minimal` builds it too (get() reads after isset(), so this is a fallback).
     *
     * @param mixed $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        $this->buildMinimalIfMissing($key);
        return parent::offsetGet($key);
    }

    /**
     * Build `minimal` through MinimalEditorConfig's Injector factory and register it under its
     * name, as HTMLEditorConfig::set_config() would.
     *
     * @param mixed $key
     */
    private function buildMinimalIfMissing($key): void
    {
        if ($key !== MinimalEditorConfig::NAME || $this->building || parent::offsetExists($key)) {
            return;
        }
        $this->building = true;
        try {
            $config = Injector::inst()->create(MinimalEditorConfig::class);
            parent::offsetSet($key, $config);
            $config->setOption('editorIdentifier', $key);
        } finally {
            $this->building = false;
        }
    }
}
